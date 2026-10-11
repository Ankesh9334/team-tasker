<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientEmail;
use App\Models\InboundEmail;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query();

        // 1. Search Query
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 2. Client Type Filter
        if ($request->filled('client_type')) {
            $query->where('client_type', $request->client_type);
        }

        // 3. Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 4. Email Filter
        if ($request->filled('email')) {
            $query->where('email', 'like', "%{$request->email}%");
        }

        // 5. Date filters
        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        $clients = $query->withCount(['tickets', 'emails', 'inboundEmails'])
            ->latest()
            ->paginate($request->input('per_page', 15))
            ->withQueryString();

        // Operational Client Statistics
        $stats = [
            'total' => Client::count(),
            'active' => Client::where('status', 'active')->count(),
            'prospects' => Client::where('status', 'prospect')->count(),
            'types_breakdown' => Client::selectRaw('client_type, count(*) as count')
                ->whereNotNull('client_type')
                ->groupBy('client_type')
                ->pluck('count', 'client_type')
                ->toArray(),
            'emails_sent' => ClientEmail::where('status', 'sent')->count(),
            'emails_failed' => ClientEmail::where('status', 'failed')->count(),
            'open_tickets' => Ticket::where('status', 'open')->whereNotNull('client_id')->count(),
        ];

        $clientTypes = Client::TYPES;

        return view('admin.clients.index', compact('clients', 'stats', 'clientTypes'));
    }

    public function show($id)
    {
        $client = Client::with([
            'tickets' => function ($q) {
                $q->latest();
            },
            'emails' => function ($q) {
                $q->latest();
            },
            'inboundEmails' => function ($q) {
                $q->latest();
            },
        ])->findOrFail($id);

        // Combined communication timeline
        $timeline = collect();

        foreach ($client->emails as $email) {
            $timeline->push([
                'type' => 'sent_email',
                'title' => 'Email Sent: ' . $email->subject,
                'date' => $email->created_at,
                'status' => $email->status,
                'data' => $email,
            ]);
        }

        foreach ($client->inboundEmails as $inbound) {
            $timeline->push([
                'type' => 'received_email',
                'title' => 'Client Replied: ' . ($inbound->subject ?: 'Inbound Email'),
                'date' => $inbound->created_at,
                'status' => $inbound->status,
                'data' => $inbound,
            ]);
        }

        foreach ($client->tickets as $ticket) {
            $timeline->push([
                'type' => 'ticket',
                'title' => 'Ticket #' . $ticket->ticket_number . ': ' . $ticket->subject,
                'date' => $ticket->created_at,
                'status' => $ticket->status,
                'data' => $ticket,
            ]);
        }

        $timeline = $timeline->sortByDesc('date')->values();

        return view('admin.clients.show', compact('client', 'timeline'));
    }

    public function create()
    {
        $clientTypes = Client::TYPES;
        return view('admin.clients.create', compact('clientTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients|unique:users',
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'client_type' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,prospect',
            'email_opt_out' => 'boolean',
        ]);

        $password = $request->filled('password') ? Hash::make($request->password) : Hash::make(Str::random(24));

        Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password,
            'phone' => $request->phone,
            'company' => $request->company_name,
            'company_name' => $request->company_name,
            'website' => $request->website,
            'client_type' => $request->client_type ?: 'Other',
            'status' => $request->status,
            'is_approved' => $request->status === 'active',
            'email_opt_out' => (bool) $request->input('email_opt_out', false),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.clients.index')->with('success', 'Client created successfully.');
    }

    public function edit($id)
    {
        $client = Client::findOrFail($id);
        $clientTypes = Client::TYPES;

        return view('admin.clients.edit', compact('client', 'clientTypes'));
    }

    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients,email,' . $id . '|unique:users',
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'client_type' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,prospect',
            'email_opt_out' => 'boolean',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'company' => $request->company_name,
            'company_name' => $request->company_name,
            'website' => $request->website,
            'client_type' => $request->client_type ?: 'Other',
            'status' => $request->status,
            'is_approved' => $request->status === 'active',
            'email_opt_out' => (bool) $request->input('email_opt_out', false),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $client->update($data);

        return redirect()->route('admin.clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);

        if ($client->tickets()->exists()) {
            return back()->with('error', 'Cannot delete client with associated tickets.');
        }

        $client->delete();

        return redirect()->route('admin.clients.index')->with('success', 'Client deleted successfully.');
    }

    /**
     * Export clients to CSV with formula injection protection.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('client_type')) {
            $query->where('client_type', $request->client_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="clients_' . date('Y-m-d_H-i') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            
            // Output UTF-8 BOM
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Header
            fputcsv($file, [
                'ID',
                'Name',
                'Company Name',
                'Email',
                'Phone',
                'Client Type',
                'Status',
                'Website',
                'Email Opt Out',
                'Created At',
            ]);

            $query->chunk(250, function ($clients) use ($file) {
                foreach ($clients as $client) {
                    fputcsv($file, [
                        $client->id,
                        $this->sanitizeCsvField($client->name),
                        $this->sanitizeCsvField($client->company_name ?? $client->company),
                        $this->sanitizeCsvField($client->email),
                        $this->sanitizeCsvField($client->phone),
                        $this->sanitizeCsvField($client->client_type),
                        $this->sanitizeCsvField($client->status),
                        $this->sanitizeCsvField($client->website),
                        $client->email_opt_out ? 'Yes' : 'No',
                        $client->created_at ? $client->created_at->format('Y-m-d H:i:s') : '',
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Prevent CSV formula injection attacks by escaping dangerous prefixes.
     */
    protected function sanitizeCsvField(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Prevent formula injection if starts with =, +, -, @, tab, or carriage return
        if (preg_match('/^[=\+\-@\t\r]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
