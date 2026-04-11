<?php

namespace App\Http\Controllers\Api;

use Shared\Http\Controllers\BaseApiController;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Shared\Http\Traits\TenantForwarding;

class ReportController extends BaseApiController
{
    use TenantForwarding;

    public function candidateMetrics()
    {
        try {
            $response = $this->serviceHttp()->get(env('CANDIDATE_SERVICE_URL', 'http://candidate-service:8080') . '/api/candidates/metrics/stats');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            Log::error("Candidate service metrics error: " . $response->status());
            return response()->json(['error' => 'Failed to fetch candidate metrics', 'status' => $response->status()], 502);
        } catch (\Exception $e) {
            Log::error("Candidate service metrics fetch exception: " . $e->getMessage());
            return response()->json(['error' => 'Candidate service unavailable'], 503);
        }
    }

    public function vacancyMetrics()
    {
        try {
            $response = $this->serviceHttp()->get(env('VACANCY_SERVICE_URL', 'http://vacancy-service:8080') . '/api/vacancies/metrics/stats');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            Log::error("Vacancy service metrics error: " . $response->status());
            return response()->json(['error' => 'Failed to fetch vacancy metrics', 'status' => $response->status()], 502);
        } catch (\Exception $e) {
            Log::error("Vacancy service metrics fetch exception: " . $e->getMessage());
            return response()->json(['error' => 'Vacancy service unavailable'], 503);
        }
    }

    public function hiringPipeline()
    {
        try {
            $candidateResponse = $this->serviceHttp()->get(env('CANDIDATE_SERVICE_URL', 'http://candidate-service:8080') . '/api/candidates/metrics/stats');
            
            if ($candidateResponse->successful()) {
                $data = $candidateResponse->json();
                $byStatus = $data['by_status'] ?? [];
                
                $pipeline = [
                    'screening' => ($byStatus['new'] ?? 0) + ($byStatus['reviewing'] ?? 0),
                    'shortlisted' => $byStatus['shortlisted'] ?? 0,
                    'interview' => $byStatus['interviewed'] ?? 0,
                    'offer' => $byStatus['offered'] ?? 0,
                    'hired' => $byStatus['hired'] ?? 0,
                ];
                
                return response()->json($pipeline);
            }
            
            Log::error("Pipeline data fetch error: " . $candidateResponse->status());
            return response()->json(['error' => 'Failed to fetch pipeline data'], 502);
        } catch (\Exception $e) {
            Log::error("Pipeline data fetch exception: " . $e->getMessage());
            return response()->json(['error' => 'Service unavailable'], 503);
        }
    }

    public function performance()
    {
        try {
            $vacancyResponse = $this->serviceHttp()->get(env('VACANCY_SERVICE_URL', 'http://vacancy-service:8080') . '/api/vacancies/metrics/stats');
            $offerResponse = $this->serviceHttp()->get(env('OFFER_SERVICE_URL', 'http://offer-service:8080') . '/api/offers/metrics/stats');
            $interviewResponse = $this->serviceHttp()->get(env('INTERVIEW_SERVICE_URL', 'http://interview-service:8080') . '/api/interviews/metrics/stats');
            
            $avgTimeFill = $vacancyResponse->successful() ? ($vacancyResponse->json()['avg_time_to_fill'] ?? 'N/A') : 'N/A';
            $acceptanceRate = $offerResponse->successful() ? ($offerResponse->json()['acceptance_rate'] ?? 'N/A') : 'N/A';
            
            $interviewToOffer = 'N/A';
            if ($interviewResponse->successful() && $offerResponse->successful()) {
                $totalInterviews = $interviewResponse->json()['total_interviews'] ?? 0;
                $totalOffers = $offerResponse->json()['total_offers'] ?? 0;
                
                if ($totalInterviews > 0) {
                    $interviewToOffer = round(($totalOffers / $totalInterviews) * 100) . '%';
                }
            }

            return response()->json([
                'avg_time_to_hire' => $avgTimeFill,
                'offer_acceptance_rate' => $acceptanceRate,
                'interview_to_offer_ratio' => $interviewToOffer,
            ]);
        } catch (\Exception $e) {
            Log::error("Performance metrics calculation failed: " . $e->getMessage());
            return response()->json(['error' => 'Failed to calculate performance metrics'], 503);
        }
    }

    /**
     * Dashboard - aggregations of all metrics.
     */
    public function dashboard()
    {
        $candidateResponse = $this->candidateMetrics();
        $vacancyResponse = $this->vacancyMetrics();
        $pipelineResponse = $this->hiringPipeline();
        $performanceResponse = $this->performance();
        
        return response()->json([
            'candidates' => json_decode($candidateResponse->getContent(), true),
            'vacancies' => json_decode($vacancyResponse->getContent(), true),
            'pipeline' => json_decode($pipelineResponse->getContent(), true),
            'performance' => json_decode($performanceResponse->getContent(), true),
        ]);
    }

    /**
     * AI metrics - usage by provider/model/service.
     */
    public function aiMetrics()
    {
        try {
            $response = $this->serviceHttp()->get(env('AI_SERVICE_URL', 'http://ai-service:8080') . '/api/metrics');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'total_requests' => 0,
                'success_rate' => 0,
                'avg_duration_ms' => 0,
                'by_service' => [],
                'by_provider' => [],
            ]);
        } catch (\Exception $e) {
            Log::error("AI metrics fetch failed: " . $e->getMessage());
            return response()->json(['error' => 'AI metrics unavailable'], 503);
        }
    }

    /**
     * AI failover statistics.
     */
    public function aiFailoverStats()
    {
        try {
            $response = $this->serviceHttp()->get(env('AI_SERVICE_URL', 'http://ai-service:8080') . '/api/failover-stats');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'failover_rate' => 0,
                'by_service' => [],
            ]);
        } catch (\Exception $e) {
            Log::error("AI failover stats fetch failed: " . $e->getMessage());
            return response()->json(['error' => 'Failover stats unavailable'], 503);
        }
    }
}
