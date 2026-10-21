<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'description', 'created_by'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /** Holiday kitne din ki hai (end_date na ho to 1 din). */
    public function getDaysCountAttribute(): int
    {
        $end = ($this->end_date ?? $this->start_date)->copy()->startOfDay();

        return (int) abs($this->start_date->copy()->startOfDay()->diffInDays($end)) + 1;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
