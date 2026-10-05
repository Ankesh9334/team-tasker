<?php

namespace App\Http\Controllers;

use App\Jobs\SendClientEmailJob;
use App\Models\Client;
use App\Models\ClientEmail;
use App\Models\ClientEmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientEmailController extends Controller
{
    /**
     * Show compose screen for single or bulk client email.
     */
    public function compose(Request $request)
    {
        $clientIds = $request->input('client_ids');
        if (is_string($clientIds)) {
            $clientIds = array_filter(explode(',', $clientIds));
        }

        $clientType = $request->input('client_type');
        $status = $request->input('status');

        $query = Client::query();

        if (!empty($clientIds)) {
            $query->whereIn('id', $clientIds);
        } else {
            if (!empty($clientType)) {
                $query->where('client_type', $clientType);
            }
            if (!empty($status)) {
                $query->where('status', $status);
            }
        }

        $allMatching = $query->get();

        // Calculate recipient analysis
        $totalMatching = $allMatching->count();
        $validEmails = $allMatching->filter(fn($c) => !empty($c->email) && filter_var($c->email, FILTER_VALIDATE_EMAIL) && !$c->email_opt_out);
        $missingEmails = $allMatching->filter(fn($c) => empty($c->email) || !filter_var($c->email, FILTER_VALIDATE_EMAIL));
        $optedOut = $allMatching->filter(fn($c) => (bool) $c->email_opt_out);

        $templates = ClientEmailTemplate::where('status', 'active')->orderBy('name')->get();
        $allClients = Client::orderBy('name')->get(['id', 'name', 'company', 'company_name', 'email', 'client_type', 'status']);
        $clientTypes = Client::TYPES;

        return view('admin.clients.emails.compose', compact(
            'allMatching',
            'totalMatching',
            'validEmails',
            'missingEmails',
            'optedOut',
            'templates',
            'clientIds',
            'clientType',
            'status',
            'allClients',
            'clientTypes'
        ));
    }

    /**
     * Send single or bulk email to clients via Queue.
     */
    public function send(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'client_ids' => 'nullable|array',
            'client_ids.*' => 'exists:clients,id',
            'attachments.*' => 'nullable|file|max:10240', // 10MB each
        ]);

        $clientIds = $request->input('client_ids', []);
        $clientType = $request->input('client_type');
        $status = $request->input('status');

        $query = Client::query();

        if (!empty($clientIds)) {
            $query->whereIn('id', $clientIds);
        } else {
            if (!empty($clientType)) {
                $query->where('client_type', $clientType);
            }
            if (!empty($status)) {
                $query->where('status', $status);
            }
        }

        $recipients = $query->whereNotNull('email')
            ->where('email_opt_out', false)
            ->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No valid, eligible client email addresses found for sending.');
        }

        // Handle uploaded attachments
        $storedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('email_attachments', 'public');
                    $storedAttachments[] = [
                        'path' => $path,
                        'name' => $file->getClientOriginalName(),
                        'mime' => $file->getClientMimeType(),
                    ];
                }
            }
        }

        $campaignId = Str::uuid()->toString();
        $campaignName = $request->input('campaign_name') ?: ($request->subject . ' (' . now()->format('d M Y H:i') . ')');
        $templateId = $request->input('template_id');

        $dispatchedCount = 0;
        $currentUser = auth()->user();

        foreach ($recipients as $client) {
            if (!filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // Render variables specifically for this client
            $rendered = ClientEmailTemplate::renderContent(
                $request->subject,
                $request->body,
                $client,
                $currentUser
            );

            $emailRecord = ClientEmail::create([
                'client_id' => $client->id,
                'client_email_template_id' => $templateId ?: null,
                'campaign_id' => $campaignId,
                'campaign_name' => $campaignName,
                'to_email' => $client->email,
                'to_name' => $client->name,
                'from_email' => config('mail.from.address'),
                'from_name' => config('mail.from.name', 'Team Tasker'),
                'subject' => $rendered['subject'],
                'body' => $rendered['body'],
                'attachments' => $storedAttachments,
                'status' => 'queued',
                'sent_by' => $currentUser?->id,
            ]);

            // Dispatch job to queue
            SendClientEmailJob::dispatch($emailRecord->id);
            $dispatchedCount++;
        }

        return redirect()->route('admin.clients.emails.queue')
            ->with('success', "{$dispatchedCount} emails queued for delivery.");
    }

    /**
     * Email Outbox & Queue Monitoring.
     */
    public function index(Request $request)
    {
        $query = ClientEmail::with(['client', 'sender'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('to_email', 'like', "%{$search}%")
                    ->orWhere('to_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $emails = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => ClientEmail::count(),
            'queued' => ClientEmail::where('status', 'queued')->count(),
            'sending' => ClientEmail::where('status', 'sending')->count(),
            'sent' => ClientEmail::where('status', 'sent')->count(),
            'failed' => ClientEmail::where('status', 'failed')->count(),
        ];

        return view('admin.clients.emails.queue', compact('emails', 'stats'));
    }

    /**
     * View detailed sent email.
     */
    public function show($id)
    {
        $email = ClientEmail::with(['client', 'template', 'ticket', 'sender'])->findOrFail($id);
        return view('admin.clients.emails.show', compact('email'));
    }

    /**
     * Retry a failed email.
     */
    public function retry($id)
    {
        $email = ClientEmail::findOrFail($id);

        if ($email->status === 'sent') {
            return back()->with('info', 'This email has already been sent successfully.');
        }

        $email->update([
            'status' => 'queued',
            'failure_reason' => null,
            'failed_at' => null,
        ]);

        SendClientEmailJob::dispatch($email->id);

        return back()->with('success', 'Email has been requeued for sending.');
    }
}
