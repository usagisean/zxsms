<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    protected $fillable = [
        'sms_order_id', 'provider_activation_id', 'type', 'code', 'text', 'received_at', 'raw',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'raw' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(SmsOrder::class, 'sms_order_id');
    }
}
