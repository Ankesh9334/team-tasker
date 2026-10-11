<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboundEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'in_reply_to',
        'references',
        'from_email',
        'from_name',
        'to_email',
        'subject',
        'body_text',
        'body_html',
        'attachments',
        'client_id',
        'ticket_id',
        'ticket_reply_id',
        'status',
        'raw_payload',
    ];

    protected $casts = [
        'attachments' => 'array',
        'raw_payload' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function ticketReply()
    {
        return $this->belongsTo(TicketReply::class);
    }
}
