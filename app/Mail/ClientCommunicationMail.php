<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ClientCommunicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $htmlContent;
    public array $fileAttachments;
    public array $customHeaders;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $emailSubject,
        string $htmlContent,
        array $fileAttachments = [],
        array $customHeaders = []
    ) {
        $this->emailSubject = $emailSubject;
        $this->htmlContent = $htmlContent;
        $this->fileAttachments = $fileAttachments;
        $this->customHeaders = $customHeaders;
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
            htmlString: $this->htmlContent,
        );
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        $messageId = $this->customHeaders['Message-ID'] ?? null;
        $references = $this->customHeaders['References'] ?? [];
        if (is_string($references)) {
            $references = array_filter(explode(' ', $references));
        }

        $textHeaders = [];
        if (!empty($this->customHeaders['In-Reply-To'])) {
            $textHeaders['In-Reply-To'] = $this->customHeaders['In-Reply-To'];
        }
        if (!empty($this->customHeaders['X-TeamTasker-Client-ID'])) {
            $textHeaders['X-TeamTasker-Client-ID'] = (string) $this->customHeaders['X-TeamTasker-Client-ID'];
        }
        if (!empty($this->customHeaders['X-TeamTasker-Email-ID'])) {
            $textHeaders['X-TeamTasker-Email-ID'] = (string) $this->customHeaders['X-TeamTasker-Email-ID'];
        }
        if (!empty($this->customHeaders['X-TeamTasker-Ticket-ID'])) {
            $textHeaders['X-TeamTasker-Ticket-ID'] = (string) $this->customHeaders['X-TeamTasker-Ticket-ID'];
        }

        return new Headers(
            messageId: $messageId,
            references: $references,
            text: $textHeaders,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->fileAttachments as $att) {
            if (is_string($att)) {
                // Check if path is in storage
                if (Storage::disk('public')->exists($att)) {
                    $attachments[] = Attachment::fromStorageDisk('public', $att);
                } elseif (file_exists($att)) {
                    $attachments[] = Attachment::fromPath($att);
                }
            } elseif (is_array($att) && !empty($att['path'])) {
                $name = $att['name'] ?? null;
                $mime = $att['mime'] ?? null;
                if (Storage::disk('public')->exists($att['path'])) {
                    $attachments[] = Attachment::fromStorageDisk('public', $att['path'])
                        ->as($name)
                        ->withMime($mime);
                } elseif (file_exists($att['path'])) {
                    $attachments[] = Attachment::fromPath($att['path'])
                        ->as($name)
                        ->withMime($mime);
                }
            }
        }

        return $attachments;
    }
}
