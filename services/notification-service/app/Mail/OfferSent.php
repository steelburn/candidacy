<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * OfferSent Mailable
 * 
 * Notification sent to candidates when a job offer is extended.
 */
class OfferSent extends Mailable
{
    use Queueable, SerializesModels;

    public string $candidateName;
    public string $positionTitle;
    public string $companyName;
    public string $salaryOffered;
    public string $startDate;
    public string $expiryDate;
    public ?string $benefits;
    public ?string $notes;
    public ?string $portalUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->candidateName = $data['candidate_name'] ?? 'Candidate';
        $this->positionTitle = $data['position_title'] ?? 'the position';
        $this->companyName = $data['company_name'] ?? config('app.name', 'Candidacy');
        $this->salaryOffered = $data['salary_offered'] ?? '';
        $this->startDate = $data['start_date'] ?? '';
        $this->expiryDate = $data['expiry_date'] ?? '';
        $this->benefits = $data['benefits'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->portalUrl = $data['portal_url'] ?? null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Job Offer - {$this->positionTitle} at {$this->companyName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.offer-sent',
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
