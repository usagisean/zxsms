<?php

namespace App\Models\Sms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class SmsInventoryCard extends Model
{
    use SoftDeletes;

    const STATUS_AVAILABLE = 'available';
    const STATUS_SOLD = 'sold';
    const STATUS_DISABLED = 'disabled';
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'cdk_code', 'service_code', 'service_name', 'country_code', 'country_name',
        'phone_number', 'sms_url', 'cost_cny', 'sale_price', 'status', 'user_id',
        'sms_order_id', 'sms_code', 'sms_text', 'valid_until', 'sold_at',
        'last_polled_at', 'raw',
    ];

    protected $casts = [
        'cost_cny' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'valid_until' => 'datetime',
        'sold_at' => 'datetime',
        'last_polled_at' => 'datetime',
        'raw' => 'array',
    ];

    public function setSmsUrlAttribute($value)
    {
        $value = (string) $value;
        $this->attributes['sms_url'] = $value === '' ? '' : Crypt::encryptString($value);
    }

    public function getSmsUrlAttribute($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function order()
    {
        return $this->belongsTo(SmsOrder::class, 'sms_order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
