<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsRechargePlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'amount', 'bonus_amount', 'badge', 'is_enabled', 'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'is_enabled' => 'boolean',
    ];

    public function rechargeOrders()
    {
        return $this->hasMany(SmsRechargeOrder::class, 'plan_id');
    }
}
