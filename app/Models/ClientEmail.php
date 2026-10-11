<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'client_email_template_id',
        'campaign_id',
        'campaign_name',
        'to_email',
        'to_name',
        'from_email',
        'from_name',
        'subject',
        'body',
        'attachments',
        'status',
        'message_id',
        'ticket_id',
        'failure_reason',
        'failed_at',
        'sent_at',
        'sent_by',
    ];

    protected $casts = [
        'attachments' => 'array',
        'failed_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function template()
    {
        return $this->belongsTo(ClientEmailTemplate::class, 'client_email_template_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
