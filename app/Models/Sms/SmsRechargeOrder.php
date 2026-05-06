<?php

namespace App\Models\Sms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsRechargeOrder extends Model
{
    use SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id', 'plan_id', 'recharge_sn', 'token', 'payment_sn', 'method_code',
        'driver', 'pay_check', 'amount', 'bonus_amount', 'total_amount', 'currency',
        'trade_no', 'status', 'paid_at', 'expires_at', 'request_payload', 'notify_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'request_payload' => 'array',
        'notify_payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SmsRechargePlan::class, 'plan_id');
    }
}
