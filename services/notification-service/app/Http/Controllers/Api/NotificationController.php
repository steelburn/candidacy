<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotification;
use App\Mail\GenericNotification;
use App\Mail\InterviewScheduled;
use App\Mail\OfferSent;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * NotificationController
 * 
 * Handles sending email notifications, managing templates,
 * and tracking notification history.
 */
class NotificationController extends Controller
{
    /**
     * Send a notification email
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient' => 'required|email',
            'recipient_name' => 'nullable|string|max:255',
            'subject' => 'required_without:template_type|string|max:255',
            'message' => 'required_without:template_type|string',
            'template_type' => 'nullable|string',
            'template_data' => 'nullable|array',
            'type' => 'required|string|max:50',
            'metadata' => 'nullable|array',
            'queue' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $recipientEmail = $request->recipient;
            $recipientName = $request->recipient_name ?? '';
            $type = $request->type;
            $metadata = $request->metadata ?? [];
            $shouldQueue = $request->queue ?? true;

            // Check if using a template
            if ($request->template_type) {
                $template = NotificationTemplate::findByType($request->template_type);
                
                if (!$template) {
                    return response()->json([
                        'error' => 'Template not found',
                        'template_type' => $request->template_type
                    ], 404);
                }

                $templateData = $request->template_data ?? [];
                $subject = $template->renderSubject($templateData);
                $body = $template->renderBody($templateData);
                $templateId = $template->id;
            } else {
                $subject = $request->subject;
                $body = $request->message;
                $templateId = null;
            }

            // Create notification log
            $log = NotificationLog::createLog([
                'template_id' => $templateId,
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'subject' => $subject,
                'body' => $body,
                'type' => $type,
                'metadata' => $metadata,
            ]);

            // Send email (queued or immediate)
            if ($shouldQueue) {
                SendNotification::dispatch($log->id);
                
                Log::info('Notification queued', [
                    'log_id' => $log->id,
                    'recipient' => $recipientEmail,
                    'type' => $type,
                ]);
            } else {
                $this->sendEmail($log);
            }

            return response()->json([
                'message' => 'Notification ' . ($shouldQueue ? 'queued' : 'sent') . ' successfully',
                'notification_id' => $log->id,
                'status' => $log->status,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'error' => $e->getMessage(),
                'recipient' => $request->recipient,
            ]);

            return response()->json([
                'error' => 'Failed to send notification',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send bulk notifications
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*.email' => 'required|email',
            'recipients.*.name' => 'nullable|string',
            'subject' => 'required_without:template_type|string|max:255',
            'message' => 'required_without:template_type|string',
            'template_type' => 'nullable|string',
            'template_data' => 'nullable|array',
            'type' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $queued = 0;
        $failed = 0;

        foreach ($request->recipients as $recipient) {
            try {
                $logData = [
                    'recipient_email' => $recipient['email'],
                    'recipient_name' => $recipient['name'] ?? '',
                    'type' => $request->type,
                ];

                if ($request->template_type) {
                    $template = NotificationTemplate::findByType($request->template_type);
                    if ($template) {
                        $templateData = array_merge(
                            $request->template_data ?? [],
                            ['recipient_name' => $recipient['name'] ?? '']
                        );
                        $logData['template_id'] = $template->id;
                        $logData['subject'] = $template->renderSubject($templateData);
                        $logData['body'] = $template->renderBody($templateData);
                    }
                } else {
                    $logData['subject'] = $request->subject;
                    $logData['body'] = $request->message;
                }

                $log = NotificationLog::createLog($logData);
                SendNotification::dispatch($log->id);
                $queued++;

            } catch (\Exception $e) {
                Log::error('Failed to queue bulk notification', [
                    'recipient' => $recipient['email'],
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        Log::info('Bulk notifications queued', [
            'total_recipients' => count($request->recipients),
            'queued' => $queued,
            'failed' => $failed,
        ]);

        return response()->json([
            'message' => 'Bulk notifications queued',
            'queued' => $queued,
            'failed' => $failed,
            'total' => count($request->recipients),
        ], 201);
    }

    /**
     * Get notification templates
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function templates()
    {
        $templates = NotificationTemplate::where('is_active', true)
            ->orderBy('type')
            ->get(['id', 'name', 'subject', 'type', 'variables']);

        return response()->json($templates);
    }

    /**
     * Create a new notification template
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:notification_templates,name',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string|max:50',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template = NotificationTemplate::create($request->all());

        return response()->json($template, 201);
    }

    /**
     * Update a notification template
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTemplate(Request $request, $id)
    {
        $template = NotificationTemplate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100|unique:notification_templates,name,' . $id,
            'subject' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'type' => 'sometimes|string|max:50',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template->update($request->all());

        return response()->json($template);
    }

    /**
     * Get notification logs
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logs(Request $request)
    {
        $query = NotificationLog::with('template:id,name,type')
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('recipient')) {
            $query->where('recipient_email', 'like', '%' . $request->recipient . '%');
        }

        $logs = $query->paginate($request->per_page ?? 20);

        return response()->json($logs);
    }

    /**
     * Get a specific notification log
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showLog($id)
    {
        $log = NotificationLog::with('template')->findOrFail($id);
        return response()->json($log);
    }

    /**
     * Retry a failed notification
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function retry($id)
    {
        $log = NotificationLog::findOrFail($id);

        if ($log->status !== NotificationLog::STATUS_FAILED) {
            return response()->json([
                'error' => 'Only failed notifications can be retried',
            ], 400);
        }

        // Reset status and queue for retry
        $log->update(['status' => NotificationLog::STATUS_PENDING]);
        SendNotification::dispatch($log->id);

        return response()->json([
            'message' => 'Notification queued for retry',
            'notification_id' => $log->id,
        ]);
    }

    /**
     * Send interview scheduled notification (specialized endpoint)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendInterviewScheduled(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient' => 'required|email',
            'candidate_name' => 'required|string',
            'position_title' => 'required|string',
            'interview_date' => 'required|string',
            'interview_time' => 'required|string',
            'interview_type' => 'required|string',
            'interview_location' => 'nullable|string',
            'interviewer_names' => 'nullable|string',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $log = NotificationLog::createLog([
                'recipient_email' => $request->recipient,
                'recipient_name' => $request->candidate_name,
                'subject' => "Interview Scheduled - {$request->position_title}",
                'type' => NotificationTemplate::TYPE_INTERVIEW_SCHEDULED,
                'metadata' => array_merge($request->except(['recipient']), $request->metadata ?? []),
            ]);

            $mailable = new InterviewScheduled($request->all());
            Mail::to($request->recipient)->send($mailable);
            
            $log->markAsSent();

            Log::info('Interview scheduled notification sent', [
                'log_id' => $log->id,
                'recipient' => $request->recipient,
            ]);

            return response()->json([
                'message' => 'Interview notification sent successfully',
                'notification_id' => $log->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to send interview notification', [
                'error' => $e->getMessage(),
                'recipient' => $request->recipient,
            ]);

            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }

            return response()->json([
                'error' => 'Failed to send notification',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send offer notification (specialized endpoint)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOfferSent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient' => 'required|email',
            'candidate_name' => 'required|string',
            'position_title' => 'required|string',
            'salary_offered' => 'required|string',
            'start_date' => 'nullable|string',
            'expiry_date' => 'nullable|string',
            'benefits' => 'nullable|string',
            'notes' => 'nullable|string',
            'portal_url' => 'nullable|url',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $log = NotificationLog::createLog([
                'recipient_email' => $request->recipient,
                'recipient_name' => $request->candidate_name,
                'subject' => "Job Offer - {$request->position_title}",
                'type' => NotificationTemplate::TYPE_OFFER_SENT,
                'metadata' => array_merge($request->except(['recipient']), $request->metadata ?? []),
            ]);

            $mailable = new OfferSent($request->all());
            Mail::to($request->recipient)->send($mailable);
            
            $log->markAsSent();

            Log::info('Offer notification sent', [
                'log_id' => $log->id,
                'recipient' => $request->recipient,
            ]);

            return response()->json([
                'message' => 'Offer notification sent successfully',
                'notification_id' => $log->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to send offer notification', [
                'error' => $e->getMessage(),
                'recipient' => $request->recipient,
            ]);

            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }

            return response()->json([
                'error' => 'Failed to send notification',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actually send the email for a notification log
     * 
     * @param NotificationLog $log
     */
    protected function sendEmail(NotificationLog $log): void
    {
        try {
            $mailable = new GenericNotification(
                $log->subject,
                $log->body,
                $log->recipient_name ?? '',
                $log->type
            );

            Mail::to($log->recipient_email)->send($mailable);
            $log->markAsSent();

            Log::info('Email sent successfully', [
                'log_id' => $log->id,
                'recipient' => $log->recipient_email,
            ]);

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            
            Log::error('Failed to send email', [
                'log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
