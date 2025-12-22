<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
            'type' => 'required|in:interview_scheduled,offer_sent,candidate_status,reminder',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // In production:  would integrate with email service (SendGrid, SES, etc.)
        \Log::info('Email notification sent', $request->all());

        return response()->json([
            'message' => 'Notification sent successfully',
            'notification' => $request->all()
        ], 201);
    }

    public function sendBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Would queue bulk emails in production
        \Log::info('Bulk email notification sent', [
            'count' => count($request->recipients),
            'subject' => $request->subject
        ]);

        return response()->json([
            'message' => 'Bulk notifications queued successfully',
            'count' => count($request->recipients)
        ], 201);
    }

    public function templates()
    {
        $templates = [
            [
                'id' => 1,
                'name' => 'Interview Invitation',
                'subject' => 'Interview Scheduled - {{company_name}}',
                'body' => 'Dear {{candidate_name}}, You have been scheduled for an interview...',
            ],
            [
                'id' => 2,
                'name' => 'Offer Letter',
                'subject' => 'Job Offer - {{position}}',
                'body' => 'Congratulations {{candidate_name}}, We are pleased to offer you...',
            ],
            [
                'id' => 3,
                'name' => 'Application Status',
                'subject' => 'Application Status Update',
                'body' => 'Dear {{candidate_name}}, Your application status has been updated...',
            ],
        ];

        return response()->json($templates);
    }
}
