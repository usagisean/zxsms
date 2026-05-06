<?php

namespace App\Models\Sms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SmsWalletLog extends Model
{
    const TYPE_RECHARGE = 'recharge';
    const TYPE_SPEND = 'spend';
    const TYPE_REFUND = 'refund';
    const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'user_id', 'sms_order_id', 'recharge_order_id', 'type', 'amount',
        'balance_before', 'balance_after', 'remark', 'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(SmsOrder::class, 'sms_order_id');
    }

    public function rechargeOrder()
    {
        return $this->belongsTo(SmsRechargeOrder::class, 'recharge_order_id');
    }
}
