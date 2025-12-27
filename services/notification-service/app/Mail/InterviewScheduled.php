<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * InterviewScheduled Mailable
 * 
 * Notification sent to candidates when an interview is scheduled.
 */
class InterviewScheduled extends Mailable
{
    use Queueable, SerializesModels;

    public string $candidateName;
    public string $positionTitle;
    public string $companyName;
    public string $interviewDate;
    public string $interviewTime;
    public string $interviewType;
    public string $interviewLocation;
    public ?string $interviewerNames;
    public ?string $notes;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->candidateName = $data['candidate_name'] ?? 'Candidate';
        $this->positionTitle = $data['position_title'] ?? 'the position';
        $this->companyName = $data['company_name'] ?? config('app.name', 'Candidacy');
        $this->interviewDate = $data['interview_date'] ?? '';
        $this->interviewTime = $data['interview_time'] ?? '';
        $this->interviewType = $data['interview_type'] ?? 'interview';
        $this->interviewLocation = $data['interview_location'] ?? '';
        $this->interviewerNames = $data['interviewer_names'] ?? null;
        $this->notes = $data['notes'] ?? null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Interview Scheduled - {$this->positionTitle}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.interview-scheduled',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
