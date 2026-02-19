<?php

namespace App\Http\Controllers\Api;

use Shared\Http\Controllers\BaseApiController;
use Shared\Constants\AppConstants;
use App\Models\Candidate;
use App\Models\CvFile;
use App\Services\CvProcessingService;
use App\Services\CandidatePortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * CandidateController - Manages candidate profiles and CV operations.
 * 
 * This controller handles all candidate-related operations including:
 * - CRUD operations for candidate profiles
 * - CV upload, parsing, and text extraction (via CvProcessingService)
 * - Async CV parsing job management (via CvProcessingService)
 * - Candidate portal authentication (via CandidatePortalService)
 * - Bulk resume upload processing (via CvProcessingService)
 * 
 * @package App\Http\Controllers\Api
 * @author Candidacy Development Team
 */
class CandidateController extends BaseApiController
{
    protected CvProcessingService $cvService;
    protected CandidatePortalService $portalService;

    public function __construct(CvProcessingService $cvService, CandidatePortalService $portalService)
    {
        $this->cvService = $cvService;
        $this->portalService = $portalService;
    }

    /**
     * List all candidates with optional filtering.
     *
     * @param Request $request Query params: status, search
     * @return \Illuminate\Http\JsonResponse Paginated candidate list
     */
    public function index(Request $request)
    {
        $query = Candidate::with('latestCv');

        if ($request->has('status')) {
            $query->status($request->status);
        }

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

    /**
     * List CV parsing jobs with optional status filter.
     */
    public function listCvJobs(Request $request)
    {
        $query = \App\Models\CvParsingJob::with('candidate')
            ->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    /**
     * Retry a failed CV parsing job.
     */
    public function retryCvJob(Request $request, $id)
    {
        try {
            $mode = $request->input('mode', 'full');
            $job = $this->cvService->retryJob($id, $mode);
            
            return response()->json([
                'message' => 'Job queued for processing',
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

    /**
     * Delete a CV parsing job record.
     */
    public function deleteCvJob($id)
    {
        try {
            $this->cvService->deleteJob($id);
            return response()->json(['message' => 'CV job deleted successfully', 'id' => $id]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete job',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new candidate.
     */
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
            'skills' => 'nullable',
            'cv_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
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

        // Handle JSON fields
        foreach (['skills', 'experience', 'education'] as $field) {
            if ($request->has($field)) {
                $candidateData[$field] = Candidate::normalizeJsonField(
                    $request->$field,
                    $field === 'skills'
                );
            }
        }

        $candidateData['generated_cv_content'] = $this->cvService->generateCvContent($candidateData);

        $candidate = Candidate::create($candidateData);

        if ($request->hasFile('cv_file')) {
            $this->cvService->uploadCv($candidate, $request->file('cv_file'));
        }

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
                'sometimes', 'required', 'email',
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except('cv_file');
        
        foreach (['skills', 'experience', 'education'] as $field) {
            if ($request->has($field)) {
                $data[$field] = Candidate::normalizeJsonField($request->$field, $field === 'skills');
            }
        }

        $mergedData = array_merge($candidate->toArray(), $data);
        $data['generated_cv_content'] = $this->cvService->generateCvContent($mergedData);

        $candidate->update($data);

        if ($request->hasFile('cv_file')) {
            $this->cvService->uploadCv($candidate, $request->file('cv_file'));
        }

        return response()->json($candidate->load('latestCv'));
    }

    public function destroy($id)
    {
        $candidate = Candidate::findOrFail($id);
        
        foreach ($candidate->cvFiles as $cvFile) {
            Storage::delete($cvFile->file_path);
            $cvFile->delete();
        }

        $candidate->delete();

        return response()->json(['message' => 'Candidate deleted successfully']);
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
        $cvFile = $this->cvService->uploadCv($candidate, $request->file('cv_file'));

        return response()->json(['message' => 'CV uploaded successfully', 'cv_file' => $cvFile], 201);
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

    public function downloadCv($id)
    {
        $candidate = Candidate::findOrFail($id);
        $latestCv = $candidate->latestCv;

        if (!$latestCv) {
            abort(404, 'CV not found');
        }

        // Files are stored on the 'public' disk (storage/app/public/)
        if (!Storage::disk('public')->exists($latestCv->file_path)) {
            abort(404, 'File not found on server');
        }

        $filePath = Storage::disk('public')->path($latestCv->file_path);

        return response()->file($filePath, [
            'Content-Type' => $latestCv->mime_type,
            'Content-Disposition' => 'inline; filename="' . $latestCv->original_filename . '"'
        ]);
    }

    public function parseCv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'candidate_id' => 'nullable|exists:candidates,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->cvService->createParsingJob(
                $request->file('file'),
                $request->input('candidate_id')
            );
            
            return response()->json([
                'message' => 'CV uploaded successfully. Processing in background.',
                'job_id' => $result['job_id'],
                'status' => $result['status']
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'CV upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getCvParsingStatus($id)
    {
        $parsingJob = $this->cvService->getJobStatus($id);
        
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
        $parsingJob = $this->cvService->getJobStatus($id);
        
        if (!$parsingJob) {
            return response()->json(['error' => 'Parsing job not found'], 404);
        }

        if (in_array($parsingJob->status, ['pending', 'processing'])) {
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
        $parsingJob = $this->cvService->getJobStatus($id);
        
        if (!$parsingJob) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json([
            'id' => $parsingJob->id,
            'status' => $parsingJob->status,
            'candidate_id' => $parsingJob->candidate_id,
            'parsed_data' => $parsingJob->parsed_data,
            'error_message' => $parsingJob->error_message
        ]);
    }

    public function bulkUploadResumes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->cvService->bulkUploadResumes($request->file('files'));

        return response()->json([
            'message' => 'Bulk upload processing started',
            'jobs' => $result['jobs'],
            'failures' => $result['failures'],
            'summary' => $result['summary']
        ], 202);
    }

    public function metrics()
    {
        $totalCandidates = Candidate::count();
        
        $byStatus = Candidate::select('status', \DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
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

    // ==================== Portal Methods ====================

    public function generatePin(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:candidates,email'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->portalService->generatePin($request->email);
        
        if (isset($result['error'])) {
            return response()->json($result, 404);
        }

        return response()->json($result);
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

        $result = $this->portalService->login($request->email, $request->pin);
        
        if (isset($result['error'])) {
            return response()->json($result, 401);
        }

        return response()->json($result);
    }

    public function getPortalData(Request $request)
    {
        $token = $request->header('X-Candidate-Token');
        $candidateId = $this->portalService->validateHeaderToken($token);
        
        if (!$candidateId) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json($this->portalService->getPortalData($candidateId));
    }

    public function generateToken(Request $request, $id)
    {
        $result = $this->portalService->generateToken($id, $request->vacancy_id);
        return response()->json($result);
    }

    public function validateToken($token)
    {
        $result = $this->portalService->validateToken($token);
        
        if (!$result) {
            return response()->json(['error' => 'Invalid or expired token'], 404);
        }

        return response()->json($result);
    }

    public function submitAnswers(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer',
            'answers.*.answer' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $candidateData = $request->has('candidate') ? $request->candidate : null;
        
        $success = $this->portalService->submitAnswers(
            $token,
            $request->answers,
            $candidateData
        );

        if (!$success) {
            return response()->json(['error' => 'Invalid or expired token'], 404);
        }

        return response()->json(['message' => 'Application updated successfully']);
    }

    public function getParsingDetails($id)
    {
        $job = $this->cvService->getParsingDetails($id);

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
