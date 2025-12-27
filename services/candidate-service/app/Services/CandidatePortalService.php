<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateToken;
use App\Models\ApplicantAnswer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CandidatePortalService - Handles candidate portal authentication and data.
 * 
 * Extracted from CandidateController to improve separation of concerns.
 * 
 * @package App\Services
 */
class CandidatePortalService
{
    /**
     * Generate a PIN code for candidate authentication.
     *
     * @param string $email
     * @return array
     */
    public function generatePin(string $email): array
    {
        $candidate = Candidate::where('email', $email)->first();
        
        if (!$candidate) {
            return ['error' => 'Candidate not found'];
        }
        
        // Generate 6-digit PIN
        $pin = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Hash PIN for security
        $candidate->pin_code = Hash::make($pin);
        $candidate->save();

        Log::info("Generated PIN for {$candidate->email}: {$pin}");

        // Send PIN via notification service
        try {
            Http::post('http://notification-service:8080/api/notifications/send', [
                'type' => 'email',
                'to' => $candidate->email,
                'subject' => 'Your Candidate Portal PIN',
                'content' => "Your PIN Code is: {$pin}"
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send PIN email: " . $e->getMessage());
        }

        return [
            'message' => 'PIN code sent to your email',
            'dev_pin' => $pin // REMOVE IN PRODUCTION
        ];
    }

    /**
     * Validate PIN login and create token.
     *
     * @param string $email
     * @param string $pin
     * @return array
     */
    public function login(string $email, string $pin): array
    {
        $candidate = Candidate::where('email', $email)->first();

        if (!$candidate || !Hash::check($pin, $candidate->pin_code)) {
            return ['error' => 'Invalid credentials'];
        }

        $token = Str::random(64);
        
        CandidateToken::create([
            'candidate_id' => $candidate->id,
            'token' => $token,
            'expires_at' => now()->addHours(24),
        ]);

        return [
            'token' => $token,
            'candidate' => $candidate
        ];
    }

    /**
     * Get aggregated portal data for a candidate.
     *
     * @param int $candidateId
     * @return array
     */
    public function getPortalData(int $candidateId): array
    {
        $data = [
            'candidate' => Candidate::find($candidateId),
            'interviews' => [],
            'offers' => [],
            'matches' => []
        ];

        // Fetch Interviews
        try {
            $intRes = Http::get("http://interview-service:8080/api/candidates/{$candidateId}/interviews");
            if ($intRes->successful()) {
                $data['interviews'] = $intRes->json();
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch interviews for candidate {$candidateId}: " . $e->getMessage());
        }

        // Fetch Offers
        try {
            $offRes = Http::get("http://offer-service:8080/api/candidates/{$candidateId}/offers");
            if ($offRes->successful()) {
                $data['offers'] = $offRes->json();
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch offers for candidate {$candidateId}: " . $e->getMessage());
        }

        // Fetch Matches
        try {
            $matchRes = Http::get("http://matching-service:8080/api/candidates/{$candidateId}/matches");
            if ($matchRes->successful()) {
                $data['matches'] = $matchRes->json();
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch matches for candidate {$candidateId}: " . $e->getMessage());
        }

        return $data;
    }

    /**
     * Generate a portal access token for a candidate.
     *
     * @param int $candidateId
     * @param int|null $vacancyId
     * @return array
     */
    public function generateToken(int $candidateId, ?int $vacancyId = null): array
    {
        $candidate = Candidate::findOrFail($candidateId);
        
        $token = Str::random(64);
        
        $candidateToken = CandidateToken::create([
            'candidate_id' => $candidate->id,
            'token' => $token,
            'vacancy_id' => $vacancyId,
            'expires_at' => now()->addDays(7),
        ]);

        return [
            'token' => $token,
            'url' => $this->getPortalUrl($token),
            'expires_at' => $candidateToken->expires_at,
        ];
    }

    /**
     * Build the portal URL from settings.
     *
     * @param string $token
     * @return string
     */
    public function getPortalUrl(string $token): string
    {
        $baseUrl = null;
        
        try {
            $response = Http::timeout(1)->get('http://admin-service:8080/api/settings');
            if ($response->successful()) {
                $data = $response->json();
                $settings = $data['settings'] ?? [];
                if (!empty($settings['candidate_portal_url'])) {
                    $baseUrl = $settings['candidate_portal_url'];
                    Log::info('Using configured portal URL: ' . $baseUrl);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch settings: ' . $e->getMessage());
        }

        if (empty($baseUrl)) {
            $baseUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/' . ltrim(env('CANDIDATE_PORTAL_PATH', 'portal'), '/');
        }
        
        return rtrim($baseUrl, '/') . '/' . $token;
    }

    /**
     * Validate a portal access token.
     *
     * @param string $token
     * @return array|null
     */
    public function validateToken(string $token): ?array
    {
        $candidateToken = CandidateToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->with(['candidate'])
            ->first();

        if (!$candidateToken) {
            return null;
        }

        $candidate = $candidateToken->candidate;
        
        $answers = ApplicantAnswer::where('candidate_id', $candidate->id)
            ->where('vacancy_id', $candidateToken->vacancy_id)
            ->get();

        return [
            'candidate' => $candidate,
            'vacancy_id' => $candidateToken->vacancy_id,
            'answers' => $answers,
        ];
    }

    /**
     * Validate token from header and return candidate ID.
     *
     * @param string|null $token
     * @return int|null
     */
    public function validateHeaderToken(?string $token): ?int
    {
        if (!$token) {
            return null;
        }

        $candidateToken = CandidateToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        return $candidateToken?->candidate_id;
    }

    /**
     * Submit answers and optionally update candidate profile.
     *
     * @param string $token
     * @param array $answers
     * @param array|null $candidateData
     * @return bool
     */
    public function submitAnswers(string $token, array $answers, ?array $candidateData = null): bool
    {
        $candidateToken = CandidateToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$candidateToken) {
            return false;
        }

        foreach ($answers as $ans) {
            ApplicantAnswer::updateOrCreate(
                [
                    'candidate_id' => $candidateToken->candidate_id,
                    'vacancy_id' => $candidateToken->vacancy_id,
                    'question_id' => $ans['question_id'],
                ],
                [
                    'answer' => $ans['answer']
                ]
            );
        }
        
        if ($candidateData) {
            $candidate = Candidate::find($candidateToken->candidate_id);
            if ($candidate) {
                $candidate->update($candidateData);
            }
        }

        return true;
    }
}
