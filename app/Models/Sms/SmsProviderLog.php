<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Model;

class SmsProviderLog extends Model
{
    protected $fillable = [
        'sms_order_id', 'provider', 'action', 'method', 'url', 'request_payload',
        'response_body', 'http_status', 'duration_ms', 'is_success', 'error_message',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'is_success' => 'boolean',
    ];
}
