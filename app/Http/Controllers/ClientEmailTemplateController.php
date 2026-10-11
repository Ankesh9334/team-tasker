<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientEmailTemplate;
use Illuminate\Http\Request;

class ClientEmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = ClientEmailTemplate::with('creator')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $templates = $query->paginate(15);

        return view('admin.clients.email-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.clients.email-templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Security check: reject unsafe scripts
        $cleanBody = $this->sanitizeTemplateHtml($request->body);

        ClientEmailTemplate::create([
            'name' => $request->name,
            'subject' => $request->subject,
            'body' => $cleanBody,
            'status' => $request->status,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.clients.email-templates.index')
            ->with('success', 'Email template created successfully.');
    }

    public function edit($id)
    {
        $template = ClientEmailTemplate::findOrFail($id);
        return view('admin.clients.email-templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = ClientEmailTemplate::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        $cleanBody = $this->sanitizeTemplateHtml($request->body);

        $template->update([
            'name' => $request->name,
            'subject' => $request->subject,
            'body' => $cleanBody,
            'status' => $request->status,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.clients.email-templates.index')
            ->with('success', 'Email template updated successfully.');
    }

    public function destroy($id)
    {
        $template = ClientEmailTemplate::findOrFail($id);
        $template->delete();

        return redirect()->route('admin.clients.email-templates.index')
            ->with('success', 'Email template deleted successfully.');
    }

    /**
     * AJAX preview endpoint.
     */
    public function preview(Request $request)
    {
        $subject = $request->input('subject', '');
        $body = $request->input('body', '');
        $clientId = $request->input('client_id');

        $client = null;
        if ($clientId) {
            $client = Client::find($clientId);
        }

        if (!$client) {
            // Sample dummy client for preview
            $client = new Client([
                'name' => 'John Doe',
                'company_name' => 'Acme Corporation',
                'company' => 'Acme Corporation',
                'email' => 'john.doe@example.com',
                'phone' => '+1 (555) 234-5678',
                'client_type' => 'ERP',
                'status' => 'active',
            ]);
        }

        $rendered = ClientEmailTemplate::renderContent($subject, $body, $client, auth()->user());

        return response()->json([
            'success' => true,
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
            'client_name' => $client->name,
            'company_name' => $client->company_name ?? $client->company,
            'client_email' => $client->email,
        ]);
    }

    /**
     * Sanitize HTML to prevent <script> and javascript: execution in templates.
     */
    protected function sanitizeTemplateHtml(string $html): string
    {
        // Strip script tags
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
        // Strip inline javascript handlers (e.g. onclick, onerror)
        $html = preg_replace('#\son[a-zA-Z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)#i', '', $html);
        // Strip javascript: pseudo protocols
        $html = preg_replace('#javascript\s*:#i', 'blocked:', $html);

        return $html;
    }
}
