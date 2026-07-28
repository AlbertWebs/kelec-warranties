<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'mobile',
        'message',
        'status',
        'provider_message_id',
        'network_id',
        'response_code',
        'response_description',
        'provider_response',
        'shortcode',
        'context',
        'notification_log_id',
    ];

    protected function casts(): array
    {
        return [
            'response_code' => 'integer',
        ];
    }

    public function notificationLog(): BelongsTo
    {
        return $this->belongsTo(NotificationLog::class);
    }
}
