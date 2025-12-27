<?php

namespace App\Jobs;

use App\Mail\GenericNotification;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * SendNotification Job
 * 
 * Asynchronously sends notifications via queue.
 * Handles retries and failure logging.
 */
class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 30;

    /**
     * The notification log ID
     */
    protected int $logId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $logId)
    {
        $this->logId = $logId;
        $this->onQueue('notification_queue');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $log = NotificationLog::find($this->logId);

        if (!$log) {
            Log::warning('Notification log not found', ['log_id' => $this->logId]);
            return;
        }

        if ($log->status === NotificationLog::STATUS_SENT) {
            Log::info('Notification already sent, skipping', ['log_id' => $this->logId]);
            return;
        }

        try {
            $mailable = new GenericNotification(
                $log->subject,
                $log->body ?? '',
                $log->recipient_name ?? '',
                $log->type
            );

            Mail::to($log->recipient_email)->send($mailable);
            
            $log->markAsSent();

            Log::info('Notification sent successfully', [
                'log_id' => $log->id,
                'recipient' => $log->recipient_email,
                'type' => $log->type,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'log_id' => $log->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // If this is the last attempt, mark as failed
            if ($this->attempts() >= $this->tries) {
                $log->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $log = NotificationLog::find($this->logId);

        if ($log) {
            $log->markAsFailed($exception->getMessage());
        }

        Log::error('Notification job failed permanently', [
            'log_id' => $this->logId,
            'error' => $exception->getMessage(),
        ]);
    }
}
