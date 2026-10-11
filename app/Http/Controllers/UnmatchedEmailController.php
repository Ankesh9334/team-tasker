<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\InboundEmail;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UnmatchedEmailController extends Controller
{
    /**
     * List all unmatched inbound emails from unknown senders.
     */
    public function index(Request $request)
    {
        $query = InboundEmail::where('status', 'unmatched')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('from_email', 'like', "%{$search}%")
                    ->orWhere('from_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $unmatchedEmails = $query->paginate(15);
        $clients = Client::orderBy('name')->get(['id', 'name', 'company_name', 'email']);

        return view('admin.clients.inbound.unmatched', compact('unmatchedEmails', 'clients'));
    }

    /**
     * Assign unknown sender email to client and optionally create/append ticket.
     */
    public function assign(Request $request, $id)
    {
        $inbound = InboundEmail::findOrFail($id);

        $request->validate([
            'action_type' => 'required|in:assign_existing,create_client',
            'client_id' => 'required_if:action_type,assign_existing|nullable|exists:clients,id',
            'new_client_name' => 'required_if:action_type,create_client|nullable|string|max:255',
            'new_client_company' => 'nullable|string|max:255',
            'create_ticket' => 'nullable|boolean',
        ]);

        $client = null;

        if ($request->action_type === 'assign_existing') {
            $client = Client::findOrFail($request->client_id);
        } else {
            // Create new Client
            $client = Client::create([
                'name' => $request->new_client_name,
                'email' => $inbound->from_email,
                'company' => $request->new_client_company,
                'company_name' => $request->new_client_company,
                'status' => 'active',
                'is_approved' => true,
                'password' => Hash::make(Str::random(24)),
                'email_verified_at' => now(),
            ]);
        }

        $ticket = null;
        $reply = null;

        if ($request->boolean('create_ticket', true)) {
            $ticket = Ticket::create([
                'client_id' => $client->id,
                'subject' => Str::limit($inbound->subject ?: ('Inquiry from ' . $client->name), 250),
                'body' => $inbound->body_text ?: ($inbound->body_html ? strip_tags($inbound->body_html) : 'No content'),
                'status' => 'open',
                'priority' => 'medium',
                'email_source' => $inbound->from_email,
                'message_id' => $inbound->message_id,
                'attachments' => !empty($inbound->attachments) ? $inbound->attachments[0] : null,
            ]);

            $reply = $ticket->replies()->create([
                'client_id' => $client->id,
                'email_source' => $inbound->from_email,
                'body' => $inbound->body_text ?: ($inbound->body_html ? strip_tags($inbound->body_html) : 'No content'),
                'type' => 'client',
                'message_id' => $inbound->message_id,
                'attachments' => !empty($inbound->attachments) ? $inbound->attachments[0] : null,
                'is_private' => false,
            ]);
        }

        $inbound->update([
            'client_id' => $client->id,
            'ticket_id' => $ticket?->id,
            'ticket_reply_id' => $reply?->id,
            'status' => 'processed',
        ]);

        return back()->with('success', "Email assigned to client {$client->name}." . ($ticket ? " Ticket #{$ticket->ticket_number} created." : ''));
    }

    /**
     * Ignore an unmatched email.
     */
    public function ignore($id)
    {
        $inbound = InboundEmail::findOrFail($id);
        $inbound->update(['status' => 'ignored']);

        return back()->with('success', 'Inbound email ignored.');
    }
}
