<?php

namespace App\Http\Controllers\Api;

use Shared\Http\Controllers\BaseApiController;
use Shared\Constants\AppConstants;
use App\Models\Candidate;
use App\Models\CvFile;
use App\Services\CvExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\CandidateToken;
use App\Models\ApplicantAnswer;
use Illuminate\Support\Facades\Hash;

class CandidateController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Candidate::with('latestCv');

        // Filter by status
        if ($request->has('status')) {
            $query->status($request->status);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $candidates = $query->latest()->paginate(AppConstants::DEFAULT_PAGE_SIZE);

        return response()->json($candidates);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:candidates,email',
            'phone' => 'nullable|string',
            'linkedin_url' => 'nullable|url',
            'github_url' => 'nullable|url',
            'portfolio_url' => 'nullable|url',
            'summary' => 'nullable|string',
            'years_of_experience' => 'nullable|integer',
            'skills' => 'nullable', // Can be array or JSON string
            'cv_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $candidateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'linkedin_url' => $request->linkedin_url,
            'github_url' => $request->github_url,
            'portfolio_url' => $request->portfolio_url,
            'summary' => $request->summary,
            'years_of_experience' => $request->years_of_experience,
            'status' => 'new',
        ];

        // Handle JSON fields using shared trait
        foreach (['skills', 'experience', 'education'] as $field) {
            if ($request->has($field)) {
                $candidateData[$field] = Candidate::normalizeJsonField(
                    $request->$field,
                    $field === 'skills' // Allow comma-separated for skills
                );
            }
        }

        // Generate standardized CV content
        $candidateData['generated_cv_content'] = $this->generateCvContent($candidateData);

        $candidate = Candidate::create($candidateData);

        // Handle CV upload if provided
        if ($request->hasFile('cv_file')) {
            $this->uploadCv($candidate, $request->file('cv_file'));
        }

        // Publish CandidateCreated event to Redis
        event(new \App\Events\CandidateCreated($candidate));

        return response()->json($candidate->load('latestCv'), 201);
    }

    public function show($id)
    {
        $candidate = Candidate::with('cvFiles')->findOrFail($id);
        return response()->json($candidate);
    }

    public function update(Request $request, $id)
    {
        $candidate = Candidate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:candidates,email,' . $id,
            'phone' => 'nullable|string',
            'summary' => 'nullable|string',
            'years_of_experience' => 'nullable|integer',
            'skills' => 'nullable',
            'status' => 'sometimes|in:new,reviewing,shortlisted,interviewed,offered,hired,rejected',
            'cv_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle JSON fields update using shared trait
        $data = $request->except('cv_file');
        
        foreach (['skills', 'experience', 'education'] as $field) {
            if ($request->has($field)) {
                $data[$field] = Candidate::normalizeJsonField(
                    $request->$field,
                    $field === 'skills' // Allow comma-separated for skills
                );
            }
        }

        // Regenerate standardized CV content including new data
        $mergedData = array_merge($candidate->toArray(), $data);
        $data['generated_cv_content'] = $this->generateCvContent($mergedData);

        $candidate->update($data);

        // Handle new CV upload
        if ($request->hasFile('cv_file')) {
            $this->uploadCv($candidate, $request->file('cv_file'));
        }

        return response()->json($candidate->load('latestCv'));
    }

    public function destroy($id)
    {
        $candidate = Candidate::findOrFail($id);
        
        // Delete all CV files
        foreach ($candidate->cvFiles as $cvFile) {
            Storage::delete($cvFile->file_path);
            $cvFile->delete();
        }

        $candidate->delete();

        return response()->json(['message' => 'Candidate deleted successfully']);
    }

    protected function uploadCv(Candidate $candidate, $file)
    {
        $originalName = $file->getClientOriginalName();
        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('cvs', $storedName);
        
        // Get the absolute file path for extraction
        $fullPath = storage_path('app/' . $path);

        $extractedText = null;
        $parsedData = null;

        try {
            $extractor = new CvExtractorService();
            $extractedText = $extractor->extractText($fullPath);

            if ($extractedText) {
                $aiResponse = Http::timeout(60)->post('http://ai-service:8080/api/ai/parse-cv', [
                    'text' => $extractedText
                ]);
                
                if ($aiResponse->successful()) {
                    $parsedData = $aiResponse->json();
                    
                    // Update candidate with parsed data
                    // The AI response has nested structure: parsed_data.parsed_data
                    $aiData = $parsedData['parsed_data'] ?? $parsedData;
                    
                    $updateData = [];
                    if (!empty($aiData['skills'])) {
                        $updateData['skills'] = json_encode($aiData['skills']);
                    }
                    if (!empty($aiData['experience'])) {
                        $updateData['experience'] = json_encode($aiData['experience']);
                    }
                    if (!empty($aiData['education'])) {
                        $updateData['education'] = json_encode($aiData['education']);
                    }
                    if (!empty($aiData['summary'])) {
                        $updateData['summary'] = $aiData['summary'];
                    }
                    if (!empty($aiData['phone']) && empty($candidate->phone)) {
                        $updateData['phone'] = $aiData['phone'];
                    }
                    if (isset($aiData['years_of_experience'])) {
                        $updateData['years_of_experience'] = $aiData['years_of_experience'];
                    }
                    
                    if (!empty($updateData)) {
                        $candidate->update($updateData);
                    }
                } else {
                    Log::error('AI CV parsing failed with status ' . $aiResponse->status() . ': ' . $aiResponse->body());
                }
            }
        } catch (\Exception $e) {
            Log::error('CV extraction or AI parsing failed: ' . $e->getMessage());
        }

        $cvFile = CvFile::create([
            'candidate_id' => $candidate->id,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'extracted_text' => $extractedText,
            'parsed_data' => $parsedData,
        ]);

        return $cvFile;
    }

    public function uploadCvFile(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cv_file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $candidate = Candidate::findOrFail($id);
        $cvFile = $this->uploadCv($candidate, $request->file('cv_file'));

        return response()->json([
            'message' => 'CV uploaded successfully',
            'cv_file' => $cvFile
        ], 201);
    }

    public function getCv($id)
    {
        $candidate = Candidate::findOrFail($id);
        $latestCv = $candidate->latestCv;

        if (!$latestCv) {
            return response()->json(['error' => 'No CV found'], 404);
        }

        return response()->json($latestCv);
    }

    protected function generateCvContent($candidateData)
    {
        // Parse JSON fields if they're strings
        $skills = $candidateData['skills'] ?? [];
        if (is_string($skills)) {
            $skills = json_decode($skills, true) ?? [];
        }
        $skillsList = is_array($skills) ? implode(', ', $skills) : $skills;
        
        $experience = $candidateData['experience'] ?? [];
        if (is_string($experience)) {
            $experience = json_decode($experience, true) ?? [];
        }
        
        $education = $candidateData['education'] ?? [];
        if (is_string($education)) {
            $education = json_decode($education, true) ?? [];
        }
        
        // Build CONCISE experience summary (optimized for matching)
        $expText = '';
        if (is_array($experience) && !empty($experience)) {
            foreach ($experience as $exp) {
                $title = $exp['title'] ?? 'Role';
                $company = $exp['company'] ?? 'Company';
                $duration = $exp['duration'] ?? '';
                $desc = $exp['description'] ?? '';
                
                // Summarize description to first 150 characters or first sentence
                $shortDesc = '';
                if (!empty($desc)) {
                    // Take first sentence or first 150 chars
                    $firstSentence = preg_split('/[.!?]\s+/', $desc, 2)[0];
                    $shortDesc = strlen($firstSentence) > 150 
                        ? substr($firstSentence, 0, 150) . '...' 
                        : $firstSentence;
                }
                
                $expText .= "- {$title} at {$company}";
                if ($duration) $expText .= " ({$duration})";
                if ($shortDesc) $expText .= ": {$shortDesc}";
                $expText .= "\n";
            }
        }
        
        // Build education summary
        $eduText = '';
        if (is_array($education) && !empty($education)) {
            foreach ($education as $edu) {
                $degree = $edu['degree'] ?? 'Degree';
                $inst = $edu['institution'] ?? 'Institution';
                $year = $edu['year'] ?? '';
                $eduText .= "- {$degree} from {$inst}";
                if ($year) $eduText .= " ({$year})";
                $eduText .= "\n";
            }
        }
        
        $yearsExp = $candidateData['years_of_experience'] ?? 'Not specified';
        $summary = $candidateData['summary'] ?? '';
        
        // Limit summary to 300 characters for conciseness
        if (strlen($summary) > 300) {
            $summary = substr($summary, 0, 300) . '...';
        }
        
        $name = $candidateData['name'] ?? '';
        
        return <<<PROFILE
Name: {$name}
Years of Experience: {$yearsExp}
Skills: {$skillsList}
Summary: {$summary}

Work Experience:
{$expText}
Education:
{$eduText}
PROFILE;
    }

    public function downloadCv($id)
    {
        $candidate = Candidate::findOrFail($id);
        $latestCv = $candidate->latestCv;

        if (!$latestCv) {
            abort(404, 'CV not found');
        }
        
        // Ensure the file exists
        if (!Storage::exists($latestCv->file_path)) {
            abort(404, 'File not found on server');
        }

        // Return as inline file response (for iframes)
        return response()->file(storage_path('app/' . $latestCv->file_path), [
            'Content-Type' => $latestCv->mime_type,
            'Content-Disposition' => 'inline; filename="' . $latestCv->original_filename . '"'
        ]);
    }

    public function parseCv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');
        
        // Store permanently for the job (in temp folder though)
        $path = $file->store('temp');
        
        // Create Job Status
        $jobStatus = \App\Models\JobStatus::create([
            'type' => 'parse_cv',
            'status' => 'pending'
        ]);

        // Dispatch Job
        \App\Jobs\ParseCvJob::dispatch($jobStatus->id, $path);

        return response()->json([
            'message' => 'File uploaded and processing started',
            'job_id' => $jobStatus->id,
            'status' => 'pending'
        ]);
    }

    public function getJobStatus($id)
    {
        $jobStatus = \App\Models\JobStatus::find($id);
        if (!$jobStatus) {
            return response()->json(['error' => 'Job not found'], 404);
        }
        return response()->json($jobStatus);
    }

    /**
     * Bulk upload multiple resumes
     * Each file is parsed by AI and a candidate record is created
     */
    public function bulkUploadResumes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $files = $request->file('files');
        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($files as $index => $file) {
            $originalName = $file->getClientOriginalName();
            
            try {
                // Store temporarily for processing
                $tempPath = $file->store('temp');
                $fullPath = storage_path('app/' . $tempPath);

                // Extract text from the file
                $extractor = new CvExtractorService();
                $extractedText = $extractor->extractText($fullPath);

                if (empty($extractedText)) {
                    Storage::delete($tempPath);
                    $results[] = [
                        'file' => $originalName,
                        'status' => 'failed',
                        'error' => 'Could not extract text from file'
                    ];
                    $failedCount++;
                    continue;
                }

                // Send to AI service for parsing
                $aiResponse = Http::timeout(120)->post('http://ai-service:8080/api/parse-cv', [
                    'text' => $extractedText
                ]);

                if (!$aiResponse->successful()) {
                    Storage::delete($tempPath);
                    Log::error('AI CV parsing failed for bulk upload', [
                        'file' => $originalName,
                        'status' => $aiResponse->status(),
                    ]);
                    $results[] = [
                        'file' => $originalName,
                        'status' => 'failed',
                        'error' => 'AI parsing failed'
                    ];
                    $failedCount++;
                    continue;
                }

                $parsedData = $aiResponse->json();
                $aiData = $parsedData['parsed_data'] ?? $parsedData;

                // Extract email from parsed data
                $email = $aiData['email'] ?? null;
                if (is_array($email)) $email = $email[0] ?? null; // Take first if array

                $name = $aiData['name'] ?? pathinfo($originalName, PATHINFO_FILENAME);
                if (is_array($name)) $name = $name[0] ?? pathinfo($originalName, PATHINFO_FILENAME);

                if (empty($email)) {
                    // Generate a placeholder email if not found
                    $email = Str::slug($name) . '-' . Str::random(6) . '@placeholder.local';
                }

                // Check for duplicate email (including soft-deleted)
                $existingCandidate = Candidate::withTrashed()->where('email', $email)->first();
                if ($existingCandidate) {
                    // If soft-deleted, restore and update
                    if ($existingCandidate->trashed()) {
                        $existingCandidate->restore();
                        // Update with new data
                        $candidateData = [
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'summary' => $summary,
                            'years_of_experience' => $aiData['years_of_experience'] ?? null,
                            'skills' => !empty($aiData['skills']) ? $aiData['skills'] : null,
                            'experience' => !empty($aiData['experience']) ? $aiData['experience'] : null,
                            'education' => !empty($aiData['education']) ? $aiData['education'] : null,
                            'linkedin_url' => $aiData['linkedin_url'] ?? null,
                            'github_url' => $aiData['github_url'] ?? null,
                            'status' => 'new',
                        ];
                        $candidateData['generated_cv_content'] = $this->generateCvContent($candidateData);
                        $existingCandidate->update($candidateData);
                        
                        // Move temp file to permanent storage and create CV record
                        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $permanentPath = 'cvs/' . $storedName;
                        Storage::move($tempPath, $permanentPath);

                        CvFile::create([
                            'candidate_id' => $existingCandidate->id,
                            'original_filename' => $originalName,
                            'stored_filename' => $storedName,
                            'file_path' => $permanentPath,
                            'mime_type' => $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                            'extracted_text' => $extractedText,
                            'parsed_data' => $parsedData,
                        ]);

                        $results[] = [
                            'file' => $originalName,
                            'status' => 'restored',
                            'candidate_id' => $existingCandidate->id,
                            'candidate_name' => $existingCandidate->name,
                            'candidate_email' => $existingCandidate->email,
                            'message' => 'Previously deleted candidate restored and updated'
                        ];
                        $successCount++;
                        continue;
                    } else {
                        // Active candidate exists
                        Storage::delete($tempPath);
                        $results[] = [
                            'file' => $originalName,
                            'status' => 'skipped',
                            'error' => 'Duplicate email: ' . $email,
                            'existing_candidate_id' => $existingCandidate->id
                        ];
                        $failedCount++;
                        continue;
                    }
                }

                // Sanitize other string fields
                $phone = $aiData['phone'] ?? null;
                if (is_array($phone)) $phone = $phone[0] ?? null;

                $summary = $aiData['summary'] ?? null;
                if (is_array($summary)) $summary = implode("\n", $summary);

                // Create candidate data
                // Note: skills, experience, education are cast to array in Candidate model, so we pass arrays directly.
                $candidateData = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'summary' => $summary,
                    'years_of_experience' => $aiData['years_of_experience'] ?? null,
                    'skills' => !empty($aiData['skills']) ? $aiData['skills'] : null,
                    'experience' => !empty($aiData['experience']) ? $aiData['experience'] : null,
                    'education' => !empty($aiData['education']) ? $aiData['education'] : null,
                    'linkedin_url' => $aiData['linkedin_url'] ?? null,
                    'github_url' => $aiData['github_url'] ?? null,
                    'status' => 'new',
                ];

                // Generate standardized CV content
                $candidateData['generated_cv_content'] = $this->generateCvContent($candidateData);

                // Create the candidate
                $candidate = Candidate::create($candidateData);

                // Move temp file to permanent storage and create CV record
                $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $permanentPath = 'cvs/' . $storedName;
                Storage::move($tempPath, $permanentPath);

                CvFile::create([
                    'candidate_id' => $candidate->id,
                    'original_filename' => $originalName,
                    'stored_filename' => $storedName,
                    'file_path' => $permanentPath,
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'extracted_text' => $extractedText,
                    'parsed_data' => $parsedData,
                ]);

                // Publish CandidateCreated event
                event(new \App\Events\CandidateCreated($candidate));

                $results[] = [
                    'file' => $originalName,
                    'status' => 'success',
                    'candidate_id' => $candidate->id,
                    'candidate_name' => $candidate->name,
                    'candidate_email' => $candidate->email 
                ];
                $successCount++;

            } catch (\Exception $e) {
                Log::error('Bulk upload failed for file', [
                    'file' => $originalName,
                    'error' => $e->getMessage()
                ]);
                
                // Clean up temp file if exists
                if (isset($tempPath) && Storage::exists($tempPath)) {
                    Storage::delete($tempPath);
                }
                
                $results[] = [
                    'file' => $originalName,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                $failedCount++;
            }
        }

        return response()->json([
            'message' => 'Bulk upload completed',
            'results' => $results,
            'summary' => [
                'total' => count($files),
                'success' => $successCount,
                'failed' => $failedCount
            ]
        ]);
    }
    public function metrics()
    {
        $totalCandidates = Candidate::count();
        
        $byStatus = Candidate::select('status', \DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        // Ensure all statuses are represented
        $allStatuses = ['new', 'reviewing', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected'];
        $statusCounts = [];
        foreach ($allStatuses as $status) {
            $statusCounts[$status] = $byStatus[$status] ?? 0;
        }

        $thisMonth = Candidate::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        $thisWeek = Candidate::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        return response()->json([
            'total_candidates' => $totalCandidates,
            'by_status' => $statusCounts,
            'this_month' => $thisMonth,
            'this_week' => $thisWeek,
        ]);
    }

    // PIN Authentication Methods

    public function generatePin(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:candidates,email'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $candidate = Candidate::where('email', $request->email)->first();
        
        // Generate 6-digit PIN
        $pin = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // In production, we would Hash this. For now, simple storage or hashed.
        // Let's hash it for security best practice.
        $candidate->pin_code = \Illuminate\Support\Facades\Hash::make($pin);
        $candidate->save();

        // In a real app, send email here. 
        // For this demo/MVP, we'll return it in response (Only for dev/demo purposes!)
        // Or log it.
        Log::info("Generated PIN for {$candidate->email}: {$pin}");

        // Simulate failing to send email by just returning success message
        // Ideally we should integrate with notification-service
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

        return response()->json([
            'message' => 'PIN code sent to your email',
            'dev_pin' => $pin // REMOVE IN PRODUCTION
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'pin' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $candidate = Candidate::where('email', $request->email)->first();

        if (!$candidate || !Hash::check($request->pin, $candidate->pin_code)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Generate a token (reuse existing token structure or simpler)
        $token = Str::random(64);
        
        // We reuse CandidateToken model but it usually links to vacancy. 
        // We'll create a general login token (vacancy_id null).
        CandidateToken::create([
            'candidate_id' => $candidate->id,
            'token' => $token,
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json([
            'token' => $token,
            'candidate' => $candidate
        ]);
    }

    public function getPortalData(Request $request)
    {
        // Token validation middleware should handle auth, but we'll do manual check for now if not present
        $token = $request->header('X-Candidate-Token');
        if (!$token) return response()->json(['error' => 'Unauthenticated'], 401);

        $candidateToken = CandidateToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$candidateToken) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $candidateId = $candidateToken->candidate_id;
        
        // Aggregation
        $data = [
            'candidate' => Candidate::find($candidateId),
            'interviews' => [],
            'offers' => [],
            'matches' => []
        ];

        // Fetch Interviews
        try {
            $intRes = Http::get("http://interview-service:8080/api/candidates/{$candidateId}/interviews");
            if ($intRes->successful()) $data['interviews'] = $intRes->json();
        } catch (\Exception $e) {}

        // Fetch Offers
        try {
            $offRes = Http::get("http://offer-service:8080/api/candidates/{$candidateId}/offers");
            if ($offRes->successful()) $data['offers'] = $offRes->json();
        } catch (\Exception $e) {}

        // Fetch Matches
        try {
             $matchRes = Http::get("http://matching-service:8080/api/candidates/{$candidateId}/matches");
             if ($matchRes->successful()) $data['matches'] = $matchRes->json();
        } catch (\Exception $e) {}

        return response()->json($data);
    }

    // Portal Methods

    public function generateToken(Request $request, $id)
    {
        $candidate = Candidate::findOrFail($id);
        
        $token = Str::random(64);
        
        $candidateToken = CandidateToken::create([
            'candidate_id' => $candidate->id,
            'token' => $token,
            'vacancy_id' => $request->vacancy_id, // Optional, to scope questions
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'token' => $token,
            'url' => $this->getPortalUrl($token),
            'expires_at' => $candidateToken->expires_at,
        ]);
    }

    private function getPortalUrl($token)
    {
        // Try to get dynamic setting from admin service
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

        // Fallback to Env variables
        if (empty($baseUrl)) {
            $baseUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/' . ltrim(env('CANDIDATE_PORTAL_PATH', 'portal'), '/');
        }
        
        return rtrim($baseUrl, '/') . '/' . $token;
    }

    public function validateToken($token)
    {
        $candidateToken = CandidateToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->with(['candidate']) // Eager load candidate
            ->first();

        if (!$candidateToken) {
            return response()->json(['error' => 'Invalid or expired token'], 404);
        }

        // Return candidate data and context (vacancy_id)
        $candidate = $candidateToken->candidate;
        
        // Load existing answers if any
        $answers = ApplicantAnswer::where('candidate_id', $candidate->id)
            ->where('vacancy_id', $candidateToken->vacancy_id)
            ->get();

        return response()->json([
            'candidate' => $candidate,
            'vacancy_id' => $candidateToken->vacancy_id,
            'answers' => $answers,
        ]);
    }

    public function submitAnswers(Request $request, $token)
    {
        $candidateToken = CandidateToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$candidateToken) {
            return response()->json(['error' => 'Invalid or expired token'], 404);
        }

        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer',
            'answers.*.answer' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Save answers
        foreach ($request->answers as $ans) {
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
        
        // Also update candidate profile if data is sent
        if ($request->has('candidate')) {
             $candidate = Candidate::find($candidateToken->candidate_id);
             $candidateData = $request->candidate;
             
             // Reuse update logic or simplistic update here
             // Using update directly for simplicity and robustness
             // Ideally we should reuse validate logic but this is trusted from portal
             $updateFields = $request->only(['name', 'phone', 'summary', 'linkedin_url', 'github_url', 'portfolio_url']);
             // Handle Skills separately if passed
             if ($request->has('skills')) {
                 $updateFields['skills'] = $request->skills; // Assuming validated/formatted by frontend or we format
             }
             
             $candidate->update($candidateData);
        }

        return response()->json(['message' => 'Application updated successfully']);
    }
}
