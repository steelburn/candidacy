<?php

namespace App\Http\Controllers\Api;

use Shared\Http\Controllers\BaseApiController;
use Shared\Constants\AppConstants;
use App\Models\Interview;
use App\Models\InterviewFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InterviewController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Interview::with('feedback');

        if ($request->has('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->has('vacancy_id')) {
            $query->where('vacancy_id', $request->vacancy_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('scheduled_at', [$request->start_date, $request->end_date]);
            // When fetching for calendar, we want all events, so increase pagination limit
            $interviews = $query->orderBy('scheduled_at')->paginate(1000);
        } else {
            $interviews = $query->latest('scheduled_at')->paginate(AppConstants::DEFAULT_PAGE_SIZE);
        }

        return response()->json($interviews);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|integer',
            'vacancy_id' => 'required|integer',
            'scheduled_at' => 'required|date',
            'stage' => 'required|in:screening,technical,behavioral,final',
            'type' => 'required|in:in_person,video,phone',
            'interviewer_ids' => 'nullable|array',
            'interviewer_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $interview = Interview::create($request->all());

        // Trigger Notification
        try {
            // 1. Fetch Candidate Details
            $candidateResponse = \Illuminate\Support\Facades\Http::get("http://candidate-service:8080/api/candidates/{$request->candidate_id}");
            $candidate = $candidateResponse->json();

            // 2. Fetch Vacancy Details
            $vacancyResponse = \Illuminate\Support\Facades\Http::get("http://vacancy-service:8080/api/vacancies/{$request->vacancy_id}");
            $vacancy = $vacancyResponse->json();

            // 3. Send Notification
            if ($candidateResponse->successful() && $vacancyResponse->successful()) {
                \Illuminate\Support\Facades\Http::post('http://notification-service:8080/api/notifications/interview-scheduled', [
                    'recipient' => $candidate['email'],
                    'candidate_name' => $candidate['name'],
                    'position_title' => $vacancy['title'],
                    'interview_date' => date('l, F j, Y', strtotime($request->scheduled_at)),
                    'interview_time' => date('g:i A', strtotime($request->scheduled_at)),
                    'interview_type' => $request->type,
                    'interview_location' => $request->type === 'in_person' ? 'Office' : 'Remote Link', // Simplification
                    'metadata' => [
                        'interview_id' => $interview->id,
                        'vacancy_id' => $request->vacancy_id,
                        'candidate_id' => $request->candidate_id
                    ]
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Illuminate\Support\Facades\Log::error('Failed to trigger interview notification: ' . $e->getMessage());
        }

        return response()->json($interview, 201);
    }

    public function show($id)
    {
        $interview = Interview::with('feedback')->findOrFail($id);
        return response()->json($interview);
    }

    public function update(Request $request, $id)
    {
        $interview = Interview::findOrFail($id);
        
        // If interviewer_ids is passed, ensure it's an array for validation/casting
        if ($request->has('interviewer_ids') && !is_array($request->interviewer_ids)) {
            // It might come as a JSON string if not handled by middleware, but Laravel usually handles JSON requests.
            // Just scalar check or reliance on cast.
        }

        $interview->update($request->all());

        return response()->json($interview);
    }

    public function destroy($id)
    {
        $interview = Interview::findOrFail($id);
        $interview->delete();

        return response()->json(['message' => 'Interview deleted']);
    }

    public function addFeedback(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reviewer_id' => 'required|integer',
            'recommendation' => 'required|in:strong_hire,hire,maybe,no_hire',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $interview = Interview::findOrFail($id);

        $feedback = InterviewFeedback::create(array_merge(
            $request->all(),
            ['interview_id' => $id]
        ));

        return response()->json($feedback, 201);
    }

    public function upcoming()
    {
        $interviews = Interview::upcoming()->with('feedback')->get();
        return response()->json($interviews);
    }
    public function metrics()
    {
        $totalInterviews = Interview::count();
        
        $byStatus = Interview::select('status', \DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        // Ensure all statuses are represented
        $allStatuses = ['scheduled', 'completed', 'cancelled'];
        $statusCounts = [];
        foreach ($allStatuses as $status) {
            $statusCounts[$status] = $byStatus[$status] ?? 0;
        }

        $upcomingCount = Interview::where('scheduled_at', '>', now())->count();

        // Calculate interviews per stage
        $byStage = Interview::select('stage', \DB::raw('count(*) as count'))
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        return response()->json([
            'total_interviews' => $totalInterviews,
            'by_status' => $statusCounts,
            'upcoming_count' => $upcomingCount,
            'by_stage' => $byStage
        ]);
    }
}
