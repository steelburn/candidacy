<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscribeToEvents extends Command
{
    protected $signature = 'events:subscribe';
    protected $description = 'Subscribe to Redis events for automatic matching';

    public function handle()
    {
        $this->info('Starting event subscriber...');
        
        Redis::subscribe(['candidate.created', 'vacancy.created'], function ($message, $channel) {
            $this->info("Event received on {$channel}");
            
            try {
                $event = json_decode($message, true);
                
                if ($channel === 'candidate.created') {
                    $this->handleCandidateCreated($event);
                } elseif ($channel === 'vacancy.created') {
                    $this->handleVacancyCreated($event);
                }
            } catch (\Exception $e) {
                Log::error('Event processing failed: ' . $e->getMessage());
                $this->error('Failed to process event: ' . $e->getMessage());
            }
        });
    }

    protected function handleCandidateCreated($event)
    {
        $this->info("Processing CandidateCreated event for candidate {$event['candidate_id']}");
        
        // Get all active vacancies
        $vacancies = Http::get('http://vacancy-service:8080/api/vacancies?status=open');
        
        if (!$vacancies->successful()) {
            $this->error('Failed to fetch vacancies');
            return;
        }

        $candidateId = $event['candidate_id'];
        
        // Trigger matching for each vacancy
        foreach ($vacancies->json('data', []) as $vacancy) {
            $this->info("Matching candidate {$candidateId} with vacancy {$vacancy['id']}");
            
            try {
                Http::timeout(30)->post('http://matching-service:8080/api/matches/auto', [
                    'candidate_id' => $candidateId,
                    'vacancy_id' => $vacancy['id']
                ]);
            } catch (\Exception $e) {
                Log::error("Auto-matching failed: {$e->getMessage()}");
            }
        }
        
        $this->info("Completed matching for candidate {$candidateId}");
    }

    protected function handleVacancyCreated($event)
    {
        $this->info("Processing VacancyCreated event for vacancy {$event['vacancy_id']}");
        
        // Get all candidates
        $candidates = Http::get('http://candidate-service:8080/api/candidates?status=new,reviewing');
        
        if (!$candidates->successful()) {
            $this->error('Failed to fetch candidates');
            return;
        }

        $vacancyId = $event['vacancy_id'];
        
        // Trigger matching for each candidate
        foreach ($candidates->json('data', []) as $candidate) {
            $this->info("Matching vacancy {$vacancyId} with candidate {$candidate['id']}");
            
            try {
                Http::timeout(30)->post('http://matching-service:8080/api/matches/auto', [
                    'candidate_id' => $candidate['id'],
                    'vacancy_id' => $vacancyId
                ]);
            } catch (\Exception $e) {
                Log::error("Auto-matching failed: {$e->getMessage()}");
            }
        }
        
        $this->info("Completed matching for vacancy {$vacancyId}");
    }
}
