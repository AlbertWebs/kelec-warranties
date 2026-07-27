<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooSyncLog extends Model
{
    protected $fillable = [
        'endpoint',
        'action',
        'request_reference',
        'response_status',
        'error_message',
        'retry_count',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'retry_count' => 'integer',
            'response_status' => 'integer',
        ];
    }
}
