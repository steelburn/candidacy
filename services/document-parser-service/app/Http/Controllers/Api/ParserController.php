<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParseJob;
use App\Jobs\ParseDocumentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ParserController extends Controller
{
    public function parse(Request $request)
    {
        $supportedTypes = \Shared\Services\ConfigurationService::get('document_parser.supported_types', '{"pdf": true, "docx": true, "doc": true, "txt": true}');
        $types = is_string($supportedTypes) ? json_decode($supportedTypes, true) : $supportedTypes;
        
        $allowedMimes = [];
        if ($types['pdf'] ?? false) $allowedMimes[] = 'pdf';
        if ($types['docx'] ?? false) $allowedMimes[] = 'docx';
        if ($types['doc'] ?? false) $allowedMimes[] = 'doc';
        if ($types['txt'] ?? false) $allowedMimes[] = 'txt';
        
        $mimesRule = implode(',', $allowedMimes);

        $validator = Validator::make($request->all(), ['file' => "required|file|mimes:{$mimesRule}|max:20480"]);
        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        try {
            $file = $request->file('file');
            $path = $file->store('temp');
            
            $parseJob = ParseJob::create([
                'file_path' => $path,
                'file_type' => strtolower($file->getClientOriginalExtension()),
                'original_filename' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'status' => 'pending',
            ]);
            
            ParseDocumentJob::dispatch($parseJob->id);
            return response()->json(['message' => 'Parsing started', 'job_id' => $parseJob->id, 'status' => 'pending'], 202);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to process file', 'message' => $e->getMessage()], 500);
        }
    }

    public function status($id)
    {
        $parseJob = ParseJob::find($id);
        if (!$parseJob) return response()->json(['error' => 'Job not found'], 404);
        
        return response()->json([
            'job_id' => $parseJob->id,
            'status' => $parseJob->status,
            'file' => $parseJob->original_filename,
            'error_message' => $parseJob->error_message,
        ]);
    }

    public function result($id)
    {
        $parseJob = ParseJob::find($id);
        if (!$parseJob) return response()->json(['error' => 'Job not found'], 404);
        if (!$parseJob->isCompleted()) return response()->json(['error' => 'Job not completed', 'status' => $parseJob->status], 400);
        
        return response()->json([
            'job_id' => $parseJob->id,
            'extracted_text' => $parseJob->extracted_text,
            'page_count' => $parseJob->page_count,
        ]);
    }
}
