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

    public function listCvJobs(Request $request)
    {
        $query = \App\Models\CvParsingJob::with('candidate')
            ->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function retryCvJob(Request $request, $id)
    {
        $job = \App\Models\CvParsingJob::findOrFail($id);
        
        // Reset status
        $job->update(['status' => 'parsing_document']);

        // Dispatch to database queue for reliability
        try {
            \App\Jobs\ProcessCvParsingJob::dispatch($job->id)->onConnection('database');
            
            Log::info('Admin retry: Job dispatched to database queue', [
                'job_id' => $job->id,
                'user_id' => $request->user()->id ?? 'admin'
            ]);
            
            return response()->json([
                'message' => 'Job queued for retry',
                'job_id' => $job->id,
                'status' => 'parsing_document'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retry job',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteCvJob($id)
    {
        try {
            $job = \App\Models\CvParsingJob::findOrFail($id);
            $job->delete();
            
            return response()->json([
                'message' => 'CV job deleted successfully',
                'id' => $id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete job',
                'message' => $e->getMessage()
            ], 500);
        }
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
            'email' => [
                'sometimes',
                'required',
                'email',
                \Illuminate\Validation\Rule::unique('candidates')->ignore($id)->whereNull('deleted_at')
            ],
            'phone' => 'nullable|string',
            'summary' => 'nullable|string',
            'years_of_experience' => 'nullable|integer',
            'skills' => 'nullable',
            'status' => 'sometimes|in:draft,new,reviewing,shortlisted,interviewed,offered,hired,rejected',
            'cv_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            Log::warning('Candidate update validation failed', [
                'id' => $id,
                'errors' => $validator->errors()->toArray(),
                'input' => $request->except(['cv_file'])
            ]);
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
            Log::info('CV upload: Starting text extraction', [
                'candidate_id' => $candidate->id,
                'file_path' => $fullPath
            ]);
            
            $extractor = new CvExtractorService();
            $extractedText = $extractor->extractText($fullPath);
            
            Log::info('CV upload: Text extracted', [
                'candidate_id' => $candidate->id,
                'text_length' => strlen($extractedText ?? '')
            ]);

            if ($extractedText) {
                Log::info('CV upload: Calling AI service for parsing', [
                    'candidate_id' => $candidate->id,
                    'text_length' => strlen($extractedText),
                    'ai_service_url' => 'http://ai-service:8080/api/ai/parse-cv'
                ]);
                
                $aiResponse = Http::timeout(60)->post('http://ai-service:8080/api/ai/parse-cv', [
                    'text' => $extractedText
                ]);
                
                Log::info('CV upload: AI service response received', [
                    'candidate_id' => $candidate->id,
                    'status' => $aiResponse->status(),
                    'successful' => $aiResponse->successful()
                ]);
                
                if ($aiResponse->successful()) {
                    $parsedData = $aiResponse->json();
                    
                    // Update candidate with parsed data
                    // The AI response has nested structure: parsed_data.parsed_data
                    $aiData = $parsedData['parsed_data'] ?? $parsedData;
                    
                    Log::info('CV upload: Parsed data extracted', [
                        'candidate_id' => $candidate->id,
                        'name' => $aiData['name'] ?? 'Unknown',
                        'email' => $aiData['email'] ?? 'Not found',
                        'skills_count' => count($aiData['skills'] ?? []),
                        'experience_count' => count($aiData['experience'] ?? [])
                    ]);
                    
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
        Log::info('CV parse: Controller reached', ['has_file' => $request->hasFile('file')]);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'candidate_id' => 'nullable|exists:candidates,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            
            // Store file permanently for async processing
            $path = $file->store('cvs');
            
            Log::info('CV parse: File uploaded, creating job', [
                'file' => $originalName,
                'path' => $path
            ]);

            // Create CV parsing job immediately (without extracted text)
            $parsingJob = \App\Models\CvParsingJob::create([
                'candidate_id' => $request->input('candidate_id'),
                'file_path' => $path,
                'extracted_text' => null, // Will be filled by the job
                'status' => 'parsing_document', // Indicates document parsing in progress
            ]);

            // Dispatch async job for document parsing + AI parsing with verification
            try {
                \App\Jobs\ProcessCvParsingJob::dispatch($parsingJob->id);
                
                // Verify job was actually queued (check Redis)
                $queueLength = \Illuminate\Support\Facades\Redis::connection()->llen('queues:default');
                
                if ($queueLength === 0 || $queueLength === false) {
                    // Job didn't make it to queue - retry with database queue
                    Log::warning('CV parse: Job not in Redis queue, retrying with database', [
                        'job_id' => $parsingJob->id,
                        'queue_length' => $queueLength
                    ]);
                    
                    // Dispatch to database queue as fallback
                    \App\Jobs\ProcessCvParsingJob::dispatch($parsingJob->id)->onConnection('database');
                }
                
                Log::info('CV parse: Job created and dispatched', [
                    'job_id' => $parsingJob->id,
                    'file' => $originalName,
                    'queue_length' => $queueLength
                ]);
            } catch (\Exception $e) {
                Log::error('CV parse: Failed to dispatch job', [
                    'job_id' => $parsingJob->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Try database queue as last resort
                try {
                    \App\Jobs\ProcessCvParsingJob::dispatch($parsingJob->id)->onConnection('database');
                    Log::info('CV parse: Dispatched to database queue as fallback', [
                        'job_id' => $parsingJob->id
                    ]);
                } catch (\Exception $fallbackError) {
                    // Mark job as failed
                    $parsingJob->update(['status' => 'failed']);
                    
                    return response()->json([
                        'error' => 'Failed to queue CV processing job',
                        'message' => 'Please try again'
                    ], 500);
                }
            }
            
            return response()->json([
                'message' => 'CV uploaded successfully. Processing in background.',
                'job_id' => $parsingJob->id,
                'status' => 'parsing_document'
            ], 202);
            
        } catch (\Exception $e) {
            Log::error('CV parse: Exception occurred', [
                'file' => $originalName ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'CV upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getCvParsingStatus($id)
    {
        $parsingJob = \App\Models\CvParsingJob::find($id);
        
        if (!$parsingJob) {
            return response()->json(['error' => 'Parsing job not found'], 404);
        }

        return response()->json([
            'job_id' => $parsingJob->id,
            'status' => $parsingJob->status,
            'created_at' => $parsingJob->created_at,
            'updated_at' => $parsingJob->updated_at,
        ]);
    }

    public function getCvParsingResult($id)
    {
        $parsingJob = \App\Models\CvParsingJob::find($id);
        
        if (!$parsingJob) {
            return response()->json(['error' => 'Parsing job not found'], 404);
        }

        if ($parsingJob->status === 'pending' || $parsingJob->status === 'processing') {
            return response()->json([
                'error' => 'Job is still processing',
                'status' => $parsingJob->status
            ], 202);
        }

        if ($parsingJob->status === 'failed') {
            return response()->json([
                'error' => 'Parsing failed',
                'message' => $parsingJob->error_message
            ], 500);
        }

        return response()->json([
            'job_id' => $parsingJob->id,
            'status' => $parsingJob->status,
            'parsed_data' => $parsingJob->parsed_data,
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
    /**
     * Bulk upload multiple resumes
     * Dispatches async jobs for each file
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
        $queuedJobs = [];
        $failedUploads = [];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            
            try {
                // Generate permanent storage path immediately
                // We store directly to 'cvs/' to ensure worker has access
                $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $permanentPath = 'cvs/' . $storedName;
                
                // Store file
                Storage::put($permanentPath, file_get_contents($file));

                // Create tracking job
                $parsingJob = \App\Models\CvParsingJob::create([
                    'candidate_id' => null, // Will be linked after creation
                    'file_path' => $permanentPath,
                    'status' => 'pending',
                ]);

                // Dispatch Job
                try {
                    \App\Jobs\ProcessCvParsingJob::dispatch($parsingJob->id);
                    
                    $queuedJobs[] = [
                        'file' => $originalName,
                        'job_id' => $parsingJob->id,
                        'status' => 'pending'
                    ];

                    Log::info('Bulk upload: Job dispatched', [
                        'file' => $originalName,
                        'job_id' => $parsingJob->id
                    ]);

                } catch (\Exception $e) {
                    Log::error('Bulk upload: Failed to dispatch job', [
                        'file' => $originalName,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Mark as failed
                    $parsingJob->update(['status' => 'failed', 'error_message' => 'Dispatch failed']);
                    
                    $failedUploads[] = [
                        'file' => $originalName,
                        'error' => 'Failed to queue processing'
                    ];
                }

            } catch (\Exception $e) {
                Log::error('Bulk upload: File storage failed', [
                    'file' => $originalName,
                    'error' => $e->getMessage()
                ]);
                
                $failedUploads[] = [
                    'file' => $originalName,
                    'error' => 'Storage failed: ' . $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => 'Bulk upload processing started',
            'jobs' => $queuedJobs,
            'failures' => $failedUploads,
            'summary' => [
                'total' => count($files),
                'queued' => count($queuedJobs),
                'failed' => count($failedUploads)
            ]
        ], 202);
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
    public function getParsingDetails($id)
    {
        $candidate = \App\Models\Candidate::findOrFail($id);
        
        // Find the latest completed parsing job for this candidate
        $job = \App\Models\CvParsingJob::where('candidate_id', $id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (!$job) {
            // Check for processing jobs
             $job = \App\Models\CvParsingJob::where('candidate_id', $id)
                ->latest()
                ->first();
        }

        if (!$job) {
            return response()->json([
                'message' => 'No parsing data available for this candidate'
            ], 404);
        }

        return response()->json([
            'job_id' => $job->id,
            'file_path' => $job->file_path,
            'extracted_text' => $job->extracted_text,
            'parsed_data' => $job->parsed_data,
            'status' => $job->status,
            'created_at' => $job->created_at,
            'error_message' => $job->error_message
        ]);
    }
}
