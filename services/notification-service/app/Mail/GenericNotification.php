<?php

namespace App\Mail;

use App\Models\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * GenericNotification Mailable
 * 
 * A flexible mailable that can use either a template from the database
 * or direct subject/body content. Supports variable substitution.
 */
class GenericNotification extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $emailBody;
    public string $recipientName;
    public string $notificationType;
    public array $additionalData;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $subject,
        string $body,
        string $recipientName = '',
        string $type = 'general',
        array $additionalData = []
    ) {
        $this->emailSubject = $subject;
        $this->emailBody = $body;
        $this->recipientName = $recipientName;
        $this->notificationType = $type;
        $this->additionalData = $additionalData;
    }

    /**
     * Create from a notification template
     */
    public static function fromTemplate(
        NotificationTemplate $template,
        array $data,
        string $recipientName = ''
    ): self {
        return new self(
            $template->renderSubject($data),
            $template->renderBody($data),
            $recipientName,
            $template->type,
            $data
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: [
                'body' => $this->emailBody,
                'recipientName' => $this->recipientName,
                'type' => $this->notificationType,
                'data' => $this->additionalData,
            ],
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
