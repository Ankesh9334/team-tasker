<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'file_path',
        'imported_by',
        'total_rows',
        'imported',
        'updated',
        'skipped',
        'failed',
        'status',
        'column_mapping',
        'duplicate_handling',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'column_mapping' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_rows' => 'integer',
        'imported' => 'integer',
        'updated' => 'integer',
        'skipped' => 'integer',
        'failed' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
