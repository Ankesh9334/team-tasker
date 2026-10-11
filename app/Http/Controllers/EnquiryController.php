<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    /**
     * Show the public enquiry form.
     */
    public function create()
    {
        if (auth()->check()) {
            if (auth('client')->check()) {
                return redirect()->route('client.tickets.create');
            }

            return redirect()->route('dashboard');
        }

        return view('enquiry.create');
    }

    /**
     * Store a newly created enquiry (ticket) from a public user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        // Prepend the user's name to the body since the tickets table
        // doesn't have a dedicated "name" column for guests.
        $body = '**From:** '.$request->name."\n\n".$request->body;

        Ticket::create([
            'user_id' => null, // null for unauthenticated
            'email_source' => $request->email,
            'subject' => $request->subject,
            'body' => $body,
            'status' => 'open',
            'priority' => $request->priority,
        ]);

        return redirect()->route('enquiry.create')->with('success', 'Your enquiry has been submitted successfully. We will get back to you soon.');
    }
}
