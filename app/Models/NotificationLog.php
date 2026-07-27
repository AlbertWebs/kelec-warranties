<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'warranty_id',
        'customer_id',
        'notification_type',
        'channel',
        'recipient',
        'message',
        'status',
        'provider_response',
        'sent_at',
        'failed_at',
        'retry_count',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'retry_count' => 'integer',
        ];
    }

    public function warranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
