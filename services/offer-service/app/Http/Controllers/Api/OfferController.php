<?php

namespace App\Http\Controllers\Api;

use Shared\Http\Controllers\BaseApiController;
use Shared\Constants\AppConstants;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OfferController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Offer::query();

        if ($request->has('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $offers = $query->latest()->paginate(AppConstants::DEFAULT_PAGE_SIZE);

        return response()->json($offers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|integer',
            'vacancy_id' => 'required|integer',
            'salary_offered' => 'required|numeric',
            'offer_date' => 'required|date',
            'start_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $offer = Offer::create(array_merge(
            $request->all(),
            ['status' => 'pending']
        ));

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
                \Illuminate\Support\Facades\Http::post('http://notification-service:8080/api/notifications/offer-sent', [
                    'recipient' => $candidate['email'],
                    'candidate_name' => $candidate['name'],
                    'position_title' => $vacancy['title'],
                    'salary_offered' => '$' . number_format($request->salary_offered),
                    'start_date' => $request->start_date ? date('l, F j, Y', strtotime($request->start_date)) : null,
                    'expiry_date' => $request->expiry_date ? date('l, F j, Y', strtotime($request->expiry_date)) : null,
                    'portal_url' => "http://localhost:5173/portal/offers/{$offer->id}", // Assume portal URL structure
                    'metadata' => [
                        'offer_id' => $offer->id,
                        'vacancy_id' => $request->vacancy_id,
                        'candidate_id' => $request->candidate_id
                    ]
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Illuminate\Support\Facades\Log::error('Failed to trigger offer notification: ' . $e->getMessage());
        }

        return response()->json($offer, 201);
    }

    public function show($id)
    {
        $offer = Offer::findOrFail($id);
        return response()->json($offer);
    }

    public function update(Request $request, $id)
    {
        $offer = Offer::findOrFail($id);
        $offer->update($request->all());

        return response()->json($offer);
    }

    public function destroy($id)
    {
        $offer = Offer::findOrFail($id);
        $offer->delete();

        return response()->json(['message' => 'Offer deleted']);
    }

    public function respond(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,rejected',
            'response' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $offer = Offer::findOrFail($id);
        $offer->update([
            'status' => $request->status,
            'candidate_response' => $request->response,
            'responded_at' => now(),
        ]);

        return response()->json($offer);
    }
    public function metrics()
    {
        $totalOffers = Offer::count();
        
        $byStatus = Offer::select('status', \DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        // Ensure all statuses are represented
        $allStatuses = ['pending', 'accepted', 'rejected'];
        $statusCounts = [];
        foreach ($allStatuses as $status) {
            $statusCounts[$status] = $byStatus[$status] ?? 0;
        }

        // Calculate acceptance rate
        $totalDecided = ($statusCounts['accepted'] ?? 0) + ($statusCounts['rejected'] ?? 0);
        $acceptanceRate = $totalDecided > 0 
            ? round(($statusCounts['accepted'] / $totalDecided) * 100) . '%'
            : 'N/A';

        return response()->json([
            'total_offers' => $totalOffers,
            'by_status' => $statusCounts,
            'acceptance_rate' => $acceptanceRate,
        ]);
    }
}
