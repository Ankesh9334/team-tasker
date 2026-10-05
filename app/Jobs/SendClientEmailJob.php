<?php

namespace App\Jobs;

use App\Mail\ClientCommunicationMail;
use App\Models\ClientEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendClientEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $clientEmailId;
    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(int $clientEmailId)
    {
        $this->clientEmailId = $clientEmailId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $emailRecord = ClientEmail::with(['client', 'ticket'])->find($this->clientEmailId);

        if (!$emailRecord) {
            return;
        }

        // Check if already sent or cancelled
        if ($emailRecord->status === 'sent') {
            return;
        }

        // Check client email opt-out
        if ($emailRecord->client && $emailRecord->client->email_opt_out) {
            $emailRecord->update([
                'status' => 'cancelled',
                'failure_reason' => 'Client has opted out of email communication.',
                'failed_at' => now(),
            ]);
            return;
        }

        $emailRecord->update([
            'status' => 'sending',
        ]);

        try {
            $domain = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'teamtasker.test';
            $messageId = $emailRecord->message_id ?: sprintf('<tt-email-%d-%s@%s>', $emailRecord->id, Str::random(12), $domain);

            $customHeaders = [
                'Message-ID' => $messageId,
                'X-TeamTasker-Email-ID' => $emailRecord->id,
                'X-TeamTasker-Client-ID' => $emailRecord->client_id,
            ];

            // If email is associated with a ticket, preserve threading headers
            if ($emailRecord->ticket_id && $emailRecord->ticket) {
                $customHeaders['X-TeamTasker-Ticket-ID'] = $emailRecord->ticket_id;
                if ($emailRecord->ticket->message_id) {
                    $customHeaders['In-Reply-To'] = $emailRecord->ticket->message_id;
                    $customHeaders['References'] = $emailRecord->ticket->message_id;
                }
            }

            $mailable = new ClientCommunicationMail(
                emailSubject: $emailRecord->subject,
                htmlContent: $emailRecord->body,
                fileAttachments: $emailRecord->attachments ?? [],
                customHeaders: $customHeaders
            );

            Mail::to($emailRecord->to_email, $emailRecord->to_name)
                ->send($mailable);

            $emailRecord->update([
                'status' => 'sent',
                'message_id' => $messageId,
                'sent_at' => now(),
                'failure_reason' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendClientEmailJob failed for ClientEmail ID: ' . $emailRecord->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $emailRecord->update([
                'status' => 'failed',
                'failure_reason' => Str::limit($e->getMessage(), 1000),
                'failed_at' => now(),
            ]);

            // Re-throw so Laravel queue manages tries & backoff
            throw $e;
        }
    }
}
