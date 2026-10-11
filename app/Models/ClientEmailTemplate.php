<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientEmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'body',
        'status',
        'created_by',
        'updated_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function emails()
    {
        return $this->hasMany(ClientEmail::class);
    }

    /**
     * Render the template with client and context variables safely.
     */
    public function render(Client $client, ?User $sender = null): array
    {
        return self::renderContent($this->subject, $this->body, $client, $sender);
    }

    /**
     * Render arbitrary subject and body strings with client variables safely without eval().
     */
    public static function renderContent(string $subject, string $body, Client $client, ?User $sender = null): array
    {
        $variables = [
            '{{client_name}}' => $client->name ?? '',
            '{{company_name}}' => $client->company_name ?? $client->company ?? $client->name ?? '',
            '{{client_email}}' => $client->email ?? '',
            '{{client_type}}' => $client->client_type ?? 'Standard',
            '{{client_status}}' => ucfirst($client->status ?? 'active'),
            '{{current_date}}' => now()->format('d M Y'),
            '{{sender_name}}' => $sender?->name ?? auth()->user()?->name ?? 'Team Tasker Support',
        ];

        $renderedSubject = str_replace(array_keys($variables), array_values($variables), $subject);
        $renderedBody = str_replace(array_keys($variables), array_values($variables), $body);

        return [
            'subject' => $renderedSubject,
            'body' => $renderedBody,
        ];
    }
}
