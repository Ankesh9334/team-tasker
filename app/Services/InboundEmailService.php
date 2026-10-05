<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientEmail;
use App\Models\InboundEmail;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InboundEmailService
{
    /**
     * Parse and process an incoming email webhook payload.
     *
     * @param array|Request $payload
     * @return array [success => bool, ticket => Ticket|null, reply => TicketReply|null, inbound => InboundEmail|null, message => string]
     */
    public function processInbound(Request $request): array
    {
        $normalized = $this->normalizePayload($request);

        $fromEmail = strtolower(trim($normalized['from_email'] ?? ''));
        $fromName = trim($normalized['from_name'] ?? '');
        $subject = trim($normalized['subject'] ?? '');
        $bodyText = trim($normalized['body_text'] ?? '');
        $bodyHtml = trim($normalized['body_html'] ?? '');
        $messageId = trim($normalized['message_id'] ?? '');
        $inReplyTo = trim($normalized['in_reply_to'] ?? '');
        $references = trim($normalized['references'] ?? '');
        $toEmail = trim($normalized['to_email'] ?? '');

        if (empty($fromEmail)) {
            return [
                'success' => false,
                'message' => 'Missing from_email in incoming payload.',
            ];
        }

        // Deduplication: if this exact Message-ID was already processed, don't duplicate
        if (!empty($messageId)) {
            $existingInbound = InboundEmail::where('message_id', $messageId)->first();
            if ($existingInbound) {
                return [
                    'success' => true,
                    'inbound' => $existingInbound,
                    'ticket' => $existingInbound->ticket,
                    'reply' => $existingInbound->ticketReply,
                    'message' => 'Duplicate inbound email detected and skipped.',
                ];
            }
        } else {
            $messageId = sprintf('<inbound-%s@%s>', Str::uuid()->toString(), parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');
        }

        // Process attachments safely
        $storedAttachments = $this->handleAttachments($request, $normalized['attachments'] ?? []);

        // 1. Identify Client
        $client = Client::where('email', $fromEmail)->first();

        // 2. Identify existing Ticket via In-Reply-To, References, or Subject Tag
        $matchedTicket = $this->findExistingTicket($inReplyTo, $references, $subject, $client);

        $bodyContent = !empty($bodyText) ? $bodyText : (!empty($bodyHtml) ? strip_tags($bodyHtml) : 'No content provided.');

        // 3. Handle Unknown Sender (no client match)
        if (!$client && !$matchedTicket) {
            $inboundRecord = InboundEmail::create([
                'message_id' => $messageId,
                'in_reply_to' => $inReplyTo ?: null,
                'references' => $references ?: null,
                'from_email' => $fromEmail,
                'from_name' => $fromName ?: null,
                'to_email' => $toEmail ?: null,
                'subject' => $subject,
                'body_text' => $bodyContent,
                'body_html' => $bodyHtml ?: null,
                'attachments' => $storedAttachments,
                'client_id' => null,
                'ticket_id' => null,
                'ticket_reply_id' => null,
                'status' => 'unmatched',
                'raw_payload' => $request->all(),
            ]);

            Log::info("Inbound email from unknown sender placed in unmatched queue: {$fromEmail}", [
                'subject' => $subject,
                'inbound_id' => $inboundRecord->id,
            ]);

            return [
                'success' => true,
                'inbound' => $inboundRecord,
                'ticket' => null,
                'reply' => null,
                'message' => 'Sender not matched with any client. Logged to unmatched queue.',
            ];
        }

        // 4. If existing ticket matched -> Append as new TicketReply
        if ($matchedTicket) {
            $reply = $matchedTicket->replies()->create([
                'client_id' => $client?->id ?? $matchedTicket->client_id,
                'email_source' => $fromEmail,
                'body' => $bodyContent,
                'type' => 'client',
                'message_id' => $messageId,
                'attachments' => !empty($storedAttachments) ? $storedAttachments[0] : null,
                'is_private' => false,
            ]);

            // Reopen ticket if not open
            if (in_array($matchedTicket->status, ['closed', 'resolved', 'waiting_for_client'])) {
                $matchedTicket->update(['status' => 'open']);
            }

            $inboundRecord = InboundEmail::create([
                'message_id' => $messageId,
                'in_reply_to' => $inReplyTo ?: null,
                'references' => $references ?: null,
                'from_email' => $fromEmail,
                'from_name' => $fromName ?: null,
                'to_email' => $toEmail ?: null,
                'subject' => $subject,
                'body_text' => $bodyContent,
                'body_html' => $bodyHtml ?: null,
                'attachments' => $storedAttachments,
                'client_id' => $client?->id ?? $matchedTicket->client_id,
                'ticket_id' => $matchedTicket->id,
                'ticket_reply_id' => $reply->id,
                'status' => 'processed',
                'raw_payload' => $request->all(),
            ]);

            Log::info("Inbound reply added to existing ticket #{$matchedTicket->id}", [
                'ticket_id' => $matchedTicket->id,
                'reply_id' => $reply->id,
                'from' => $fromEmail,
            ]);

            return [
                'success' => true,
                'inbound' => $inboundRecord,
                'ticket' => $matchedTicket,
                'reply' => $reply,
                'message' => "Appended reply to existing ticket #{$matchedTicket->id}",
            ];
        }

        // 5. No existing ticket found -> Create new Ticket for matched Client
        $cleanSubject = $subject ?: ('Inquiry from ' . ($client->company_name ?? $client->name));

        $ticket = Ticket::create([
            'client_id' => $client->id,
            'subject' => Str::limit($cleanSubject, 250),
            'body' => $bodyContent,
            'status' => 'open',
            'priority' => 'medium',
            'email_source' => $fromEmail,
            'message_id' => $messageId,
            'attachments' => !empty($storedAttachments) ? $storedAttachments[0] : null,
        ]);

        $reply = $ticket->replies()->create([
            'client_id' => $client->id,
            'email_source' => $fromEmail,
            'body' => $bodyContent,
            'type' => 'client',
            'message_id' => $messageId,
            'attachments' => !empty($storedAttachments) ? $storedAttachments[0] : null,
            'is_private' => false,
        ]);

        $inboundRecord = InboundEmail::create([
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo ?: null,
            'references' => $references ?: null,
            'from_email' => $fromEmail,
            'from_name' => $fromName ?: null,
            'to_email' => $toEmail ?: null,
            'subject' => $subject,
            'body_text' => $bodyContent,
            'body_html' => $bodyHtml ?: null,
            'attachments' => $storedAttachments,
            'client_id' => $client->id,
            'ticket_id' => $ticket->id,
            'ticket_reply_id' => $reply->id,
            'status' => 'processed',
            'raw_payload' => $request->all(),
        ]);

        Log::info("New ticket #{$ticket->id} created from inbound client email", [
            'client_id' => $client->id,
            'ticket_id' => $ticket->id,
            'from' => $fromEmail,
        ]);

        return [
            'success' => true,
            'inbound' => $inboundRecord,
            'ticket' => $ticket,
            'reply' => $reply,
            'message' => "New ticket #{$ticket->id} created successfully.",
        ];
    }

    /**
     * Match incoming email to existing ticket using RFC headers and subject pattern.
     */
    protected function findExistingTicket(?string $inReplyTo, ?string $references, ?string $subject, ?Client $client): ?Ticket
    {
        // 1. Check In-Reply-To header
        if (!empty($inReplyTo)) {
            // Check ClientEmail table
            $matchedClientEmail = ClientEmail::where('message_id', $inReplyTo)->whereNotNull('ticket_id')->first();
            if ($matchedClientEmail && $matchedClientEmail->ticket) {
                return $matchedClientEmail->ticket;
            }

            // Check Ticket table directly
            $matchedTicket = Ticket::where('message_id', $inReplyTo)->first();
            if ($matchedTicket) {
                return $matchedTicket;
            }

            // Check TicketReply table
            $matchedReply = TicketReply::where('message_id', $inReplyTo)->first();
            if ($matchedReply && $matchedReply->ticket) {
                return $matchedReply->ticket;
            }
        }

        // 2. Check References header
        if (!empty($references)) {
            $refArray = is_array($references) ? $references : explode(' ', $references);
            foreach ($refArray as $ref) {
                $ref = trim($ref);
                if (empty($ref)) continue;

                $matchedClientEmail = ClientEmail::where('message_id', $ref)->whereNotNull('ticket_id')->first();
                if ($matchedClientEmail && $matchedClientEmail->ticket) {
                    return $matchedClientEmail->ticket;
                }

                $matchedTicket = Ticket::where('message_id', $ref)->first();
                if ($matchedTicket) {
                    return $matchedTicket;
                }
            }
        }

        // 3. Check Subject for ticket number tag (e.g. [#TKT-0042], [Ticket #42], #TKT-0042, #42)
        if (!empty($subject)) {
            // Regex to find #TKT-1234 or TKT-1234
            if (preg_match('/(?:#|\b)TKT-(\d+)\b/i', $subject, $matches)) {
                $ticketNumber = 'TKT-' . str_pad($matches[1], 4, '0', STR_PAD_LEFT);
                $found = Ticket::where('ticket_number', $ticketNumber)->first();
                if ($found) return $found;
                $foundById = Ticket::find((int)$matches[1]);
                if ($foundById) return $foundById;
            }

            // Regex to find [Ticket #1234] or [Ticket: 1234]
            if (preg_match('/\[(?:Ticket|TKT)[\s#:]*(\d+)\]/i', $subject, $matches)) {
                $ticketId = (int)$matches[1];
                $found = Ticket::find($ticketId);
                if ($found) return $found;
            }
        }

        return null;
    }

    /**
     * Normalize payload from different mail providers into a standard format.
     */
    protected function normalizePayload(Request $request): array
    {
        $all = $request->all();

        // 1. From email / name
        $fromRaw = $request->input('from') 
            ?? $request->input('sender') 
            ?? $request->input('From') 
            ?? $request->input('envelope.from') 
            ?? '';

        $fromEmail = '';
        $fromName = '';

        if (preg_match('/(.*)<(.+@.+?)>/', (string)$fromRaw, $matches)) {
            $fromName = trim(trim($matches[1]), '"\'');
            $fromEmail = trim($matches[2]);
        } elseif (filter_var(trim((string)$fromRaw), FILTER_VALIDATE_EMAIL)) {
            $fromEmail = trim((string)$fromRaw);
        } else {
            $fromEmail = trim((string)$fromRaw);
        }

        // 2. Subject
        $subject = $request->input('subject') 
            ?? $request->input('Subject') 
            ?? '';

        // 3. Body text & html
        $bodyText = $request->input('body_text') 
            ?? $request->input('body-plain') 
            ?? $request->input('text') 
            ?? $request->input('stripped-text') 
            ?? $request->input('Body') 
            ?? '';

        $bodyHtml = $request->input('body_html') 
            ?? $request->input('body-html') 
            ?? $request->input('html') 
            ?? $request->input('stripped-html') 
            ?? '';

        // 4. Headers: Message-ID, In-Reply-To, References
        $messageId = $request->input('message_id') 
            ?? $request->input('Message-ID') 
            ?? $request->input('message-id') 
            ?? $request->header('Message-ID') 
            ?? '';

        $inReplyTo = $request->input('in_reply_to') 
            ?? $request->input('In-Reply-To') 
            ?? $request->input('in-reply-to') 
            ?? $request->header('In-Reply-To') 
            ?? '';

        $references = $request->input('references') 
            ?? $request->input('References') 
            ?? $request->header('References') 
            ?? '';

        $toEmail = $request->input('to') 
            ?? $request->input('recipient') 
            ?? $request->input('To') 
            ?? '';

        return [
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'to_email' => is_array($toEmail) ? implode(', ', $toEmail) : (string)$toEmail,
            'subject' => (string)$subject,
            'body_text' => (string)$bodyText,
            'body_html' => (string)$bodyHtml,
            'message_id' => (string)$messageId,
            'in_reply_to' => (string)$inReplyTo,
            'references' => (string)$references,
            'attachments' => $request->input('attachments', []),
        ];
    }

    /**
     * Store inbound attachments safely in public attachments disk.
     */
    protected function handleAttachments(Request $request, array $payloadAttachments = []): array
    {
        $stored = [];
        $disallowedExtensions = ['php', 'phtml', 'exe', 'bat', 'sh', 'cmd', 'vbs', 'ps1', 'cgi', 'pl', 'jar'];

        // 1. Check multipart file uploads
        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (in_array($ext, $disallowedExtensions)) {
                        continue;
                    }
                    $path = $file->store('attachments', 'public');
                    $stored[] = $path;
                }
            }
        }

        // 2. Check base64 or url encoded attachments in JSON
        if (!empty($payloadAttachments) && is_array($payloadAttachments)) {
            foreach ($payloadAttachments as $att) {
                if (is_array($att) && !empty($att['content'])) {
                    $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $att['name'] ?? ('attachment_' . Str::random(8)));
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (in_array($ext, $disallowedExtensions)) {
                        continue;
                    }
                    $content = base64_decode($att['content']);
                    if ($content !== false) {
                        $saveName = 'attachments/' . Str::uuid() . '_' . $filename;
                        Storage::disk('public')->put($saveName, $content);
                        $stored[] = $saveName;
                    }
                }
            }
        }

        return $stored;
    }
}
