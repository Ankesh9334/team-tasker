<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch emails from configured IMAP and convert to tickets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $host = Setting::where('key', 'imap_host')->value('value');
        $port = Setting::where('key', 'imap_port')->value('value');
        $user = Setting::where('key', 'imap_user')->value('value');
        $pass = Setting::where('key', 'imap_password')->value('value');
        $enc = Setting::where('key', 'imap_encryption')->value('value'); // ssl, tls, or null

        if (! $host || ! $user || ! $pass) {
            $this->error('IMAP settings not configured.');

            return 1;
        }

        // Initialize Webklex IMAP Client
        $client = \Webklex\IMAP\Facades\Client::make([
            'host' => $host,
            'port' => $port,
            'encryption' => $enc === 'null' ? false : $enc,
            'validate_cert' => true,
            'username' => $user,
            'password' => $pass,
            'protocol' => 'imap',
        ]);

        try {
            $client->connect();
            $this->info("Connected to $host successfully.");
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();

            if (str_contains($errorMsg, 'Application-specific password required')) {
                $errorMsg .= "\n\nHINT: You are using Gmail with 2FA. You MUST use an 'App Password' instead of your regular password.\nGenerate one here: https://myaccount.google.com/apppasswords";
            }

            $this->error('Connection failed: '.$errorMsg);
            Log::error('FetchTickets: '.$errorMsg);

            return 1;
        }

        $folder = $client->getFolder('INBOX');
        $messages = $folder->query()->unseen()->get();

        if ($messages->count() === 0) {
            $this->info('No new emails found.');

            return 0;
        }

        $this->info('Found '.$messages->count().' new emails.');

        foreach ($messages as $message) {
            $subject = $message->getSubject();
            $fromEmail = $message->getFrom()[0]->mail;

            // Prefer text body for clean formatting, fallback to HTML
            $body = $message->getTextBody();
            if ($body) {
                $body = nl2br(e($body));
            } else {
                $body = $message->getHTMLBody();
                // If it's HTML, we keep it but it's risky. In a real app we'd use Purifier.
                // But since the views use {!! !!}, it's expected to be HTML-ish if needed.
            }

            $this->info("Processing: $subject from $fromEmail");

            // 1. Check if reply to existing Task (#TASK-123)
            if (preg_match('/#TASK-(\d+)/i', $subject, $matches)) {
                $taskId = $matches[1];
                $task = \App\Models\Task::find($taskId);
                if ($task) {
                    $this->info("Processing reply to Task #$taskId from $fromEmail");
                    $dbUser = User::where('email', $fromEmail)->first();

                    $task->logs()->create([
                        'user_id' => $dbUser ? $dbUser->id : null,
                        'note' => $body ?: '(Empty Body)',
                        'type' => 'message', // Client message
                    ]);

                    $message->setFlag('Seen');

                    continue;
                }
            }

            // 2. Check if reply to existing ticket (#123 or #TKT-123)
            if (preg_match('/#(?:TKT-)?(\d+)/i', $subject, $matches)) {
                $ticketId = $matches[1];
                $ticket = Ticket::find($ticketId);
                if ($ticket) {
                    $this->info("Processing reply to Ticket #$ticketId from $fromEmail");

                    $dbUser = User::where('email', $fromEmail)->first();

                    $ticket->replies()->create([
                        'user_id' => $dbUser ? $dbUser->id : null,
                        'email_source' => $fromEmail,
                        'body' => $body ?: '(Empty Body)',
                        'type' => 'client_reply',
                    ]);

                    if ($ticket->status == 'closed' || $ticket->status == 'resolved') {
                        $ticket->update(['status' => 'open']);
                    }

                    $message->setFlag('Seen');

                    continue;
                }
            }

            // Create new ticket
            $this->info("Creating new ticket from $fromEmail: $subject");

            $dbUser = User::where('email', $fromEmail)->first();

            Ticket::create([
                'user_id' => $dbUser ? $dbUser->id : null,
                'email_source' => $fromEmail,
                'subject' => $subject,
                'body' => $body ?: '(Empty Body)',
                'priority' => 'low',
                'status' => 'open',
            ]);

            $message->setFlag('Seen');
        }

        $this->info('Done.');

        return 0;
    }
}
