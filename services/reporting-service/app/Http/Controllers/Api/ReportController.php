<?php

namespace App\Http\Controllers\Api;

use Shared\Http\Controllers\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ReportController extends BaseApiController
{
    public function candidateMetrics()
    {
        try {
            $response = Http::get(env('CANDIDATE_SERVICE_URL', 'http://candidate-service:8080') . '/api/candidates/metrics/stats');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            // Fallback if service fails but we want to show something? Or just error.
            // For now, return empty structure or error.
            return response()->json(['error' => 'Failed to fetch candidate metrics'], 502);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Candidate service unavailable'], 503);
        }
    }

    public function vacancyMetrics()
    {
        try {
            $response = Http::get(env('VACANCY_SERVICE_URL', 'http://vacancy-service:8080') . '/api/vacancies/metrics/stats');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Failed to fetch vacancy metrics'], 502);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Vacancy service unavailable'], 503);
        }
    }

    public function hiringPipeline()
    {
        // This requires aggregating data from interviews and offers
        // Or we can define a pipeline based on candidate statuses or interview stages.
        // Dashboard.vue uses this.
        
        try {
            // Get candidate counts by status which maps to pipeline stages roughly
            $candidateResponse = Http::get(env('CANDIDATE_SERVICE_URL', 'http://candidate-service:8080') . '/api/candidates/metrics/stats');
            
            if ($candidateResponse->successful()) {
                $data = $candidateResponse->json();
                $byStatus = $data['by_status'];
                
                // Map candidate statuses to pipeline stages
                $pipeline = [
                    'screening' => ($byStatus['new'] ?? 0) + ($byStatus['reviewing'] ?? 0),
                    'shortlisted' => $byStatus['shortlisted'] ?? 0,
                    'interview' => $byStatus['interviewed'] ?? 0,
                    'offer' => $byStatus['offered'] ?? 0,
                    'hired' => $byStatus['hired'] ?? 0,
                ];
                
                return response()->json($pipeline);
            }
            
            return response()->json(['error' => 'Failed to fetch pipeline data'], 502);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Service unavailable'], 503);
        }
    }

    public function performance()
    {
        try {
            $vacancyResponse = Http::get(env('VACANCY_SERVICE_URL', 'http://vacancy-service:8080') . '/api/vacancies/metrics/stats');
            $offerResponse = Http::get(env('OFFER_SERVICE_URL', 'http://offer-service:8080') . '/api/offers/metrics/stats');
            
            $avgTimeFill = $vacancyResponse->successful() ? ($vacancyResponse->json()['avg_time_to_fill'] ?? 'N/A') : 'N/A';
            $acceptanceRate = $offerResponse->successful() ? ($offerResponse->json()['acceptance_rate'] ?? 'N/A') : 'N/A';
            
            // "Avg Time to Hire" is conceptually similar to "Avg Time to Fill" for this MVP, or we could calculate from candidate creation to hire.
            // Using Vacancy metrics for now.
            
            // Interview to Offer Ratio needs interview data
            // We can get total interviews and total offers
            $interviewResponse = Http::get(env('INTERVIEW_SERVICE_URL', 'http://interview-service:8080') . '/api/interviews/metrics/stats');
            
            $interviewToOffer = 'N/A';
            if ($interviewResponse->successful() && $offerResponse->successful()) {
                $totalInterviews = $interviewResponse->json()['total_interviews'] ?? 0;
                $totalOffers = $offerResponse->json()['total_offers'] ?? 0;
                
                if ($totalInterviews > 0) {
                    $interviewToOffer = round(($totalOffers / $totalInterviews) * 100) . '%';
                }
            }

            return response()->json([
                'avg_time_to_hire' => $avgTimeFill, // Using time to fill as proxy
                'offer_acceptance_rate' => $acceptanceRate,
                'interview_to_offer_ratio' => $interviewToOffer,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to calculate performance metrics'], 503);
        }
    }
}
