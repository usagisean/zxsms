<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsPaymentOrder extends Model
{
    use SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'sms_order_id', 'payment_sn', 'method_code', 'driver', 'pay_check',
        'amount', 'currency', 'trade_no', 'status', 'paid_at', 'request_payload', 'notify_payload',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'request_payload' => 'array',
        'notify_payload' => 'array',
        'amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(SmsOrder::class, 'sms_order_id');
    }
}
