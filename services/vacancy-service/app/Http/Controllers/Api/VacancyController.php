<?php

namespace App\Http\Controllers\Api;

use Shared\Http\Controllers\BaseApiController;
use Shared\Constants\AppConstants;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class VacancyController extends BaseApiController
{
// No change here, need to check routes/api.php
    public function index(Request $request)
    {
        $query = Vacancy::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('department')) {
            $query->byDepartment($request->department);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $vacancies = $query->latest()->paginate(AppConstants::DEFAULT_PAGE_SIZE);

        return response()->json($vacancies);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'work_mode' => 'nullable|array',
            'work_mode.*' => 'in:on_site,remote,hybrid',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'experience_level' => 'required|in:entry,mid,senior,lead,executive',
            'description' => 'required|string',
            'requirements' => 'nullable|array',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vacancy = Vacancy::create($request->all());

        // Publish VacancyCreated event to Redis
        event(new \App\Events\VacancyCreated($vacancy));

        return response()->json(null, 204);
    }

    public function addQuestion(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'question_text' => 'required|string',
            'question_type' => 'nullable|string|in:text,boolean,multiple_choice',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vacancy = Vacancy::findOrFail($id);
        
        $question = $vacancy->questions()->create([
            'question_text' => $request->question_text,
            'question_type' => $request->question_type ?? 'text',
        ]);

        return response()->json($question, 201);
    }

    public function getQuestions($id)
    {
        $vacancy = Vacancy::findOrFail($id);
        return response()->json($vacancy->questions);
    }

    public function show($id)
    {
        $vacancy = Vacancy::findOrFail($id);
        return response()->json($vacancy);
    }

    public function update(Request $request, $id)
    {
        $vacancy = Vacancy::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|in:draft,open,closed,on_hold',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vacancy->update($request->all());

        return response()->json($vacancy);
    }

    public function destroy($id)
    {
        $vacancy = Vacancy::findOrFail($id);
        $vacancy->delete();

        return response()->json(['message' => 'Vacancy deleted successfully']);
    }

    public function generateDescription(Request $request, $id)
    {
        $vacancy = Vacancy::findOrFail($id);

        try {
            $response = Http::post(env('AI_SERVICE_URL') . '/api/ai/generate-jd', [
                'title' => $vacancy->title,
                'department' => $vacancy->department,
                'level' => $vacancy->experience_level,
                'skills' => $vacancy->required_skills ?? [],
            ]);

            if ($response->successful()) {
                $jd = $response->json()['job_description'];
                $vacancy->update(['description' => $jd]);

                return response()->json([
                    'message' => 'Job description generated successfully',
                    'vacancy' => $vacancy
                ]);
            }

            return response()->json(['error' => 'AI service error'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate description'], 500);
        }
    }
    public function metrics()
    {
        $totalVacancies = Vacancy::count();
        
        $byStatus = Vacancy::select('status', \DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        // Calculate average time to fill (closed_at - created_at)
        // Only for closed vacancies that have a closed_at date
        // Note: We might need to make sure 'closed_at' exists in model or logic. For now, assuming basic structure.
        // If 'closed_at' is not a column, we can't calculate perfectly, but let's check basic columns.
        // Assuming 'closed_at' column exists as per standard recruiting schema, if not we skip or use updated_at for closed.
        
        // Use updated_at as proxy for closed date since closed_at column doesn't exist
        $avgDays = Vacancy::where('status', 'closed')
            ->select(\DB::raw('AVG(DATEDIFF(updated_at, created_at)) as avg_days'))
            ->value('avg_days');
            
        $avgTimeToFill = $avgDays ? round($avgDays) . ' days' : 'N/A';

        return response()->json([
            'total_vacancies' => $totalVacancies,
            'by_status' => $byStatus,
            'avg_time_to_fill' => $avgTimeToFill,
        ]);
    }
}
