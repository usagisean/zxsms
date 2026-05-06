<?php

namespace App\Models\Sms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsOrder extends Model
{
    use SoftDeletes;

    const STATUS_WAIT_PAY = 'wait_pay';
    const STATUS_PAID = 'paid';
    const STATUS_PURCHASING = 'purchasing_number';
    const STATUS_WAITING_CODE = 'waiting_code';
    const STATUS_COMPLETED = 'completed';
    const STATUS_PRICE_CHANGED = 'price_changed';
    const STATUS_PROVIDER_NO_STOCK = 'provider_no_stock';
    const STATUS_REFUND_REQUIRED = 'refund_required';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id', 'order_sn', 'token', 'service_id', 'country_id', 'service_code', 'country_code',
        'email', 'query_password_hash', 'cost_usd', 'exchange_rate', 'markup_multiplier',
        'fixed_fee', 'min_profit', 'min_price', 'sale_price', 'wallet_amount', 'currency',
        'provider_activation_id', 'provider_currency', 'provider_cost', 'phone_number',
        'sms_code', 'sms_text', 'status', 'status_note', 'buy_ip', 'paid_at',
        'wallet_paid_at', 'wallet_refunded_at', 'wallet_refund_reason',
        'purchased_at', 'code_received_at', 'expires_at', 'last_polled_at',
        'quote_snapshot', 'provider_payload',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'wallet_paid_at' => 'datetime',
        'wallet_refunded_at' => 'datetime',
        'purchased_at' => 'datetime',
        'code_received_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_polled_at' => 'datetime',
        'quote_snapshot' => 'array',
        'provider_payload' => 'array',
        'cost_usd' => 'decimal:4',
        'exchange_rate' => 'decimal:4',
        'markup_multiplier' => 'decimal:4',
        'fixed_fee' => 'decimal:2',
        'min_profit' => 'decimal:2',
        'min_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'wallet_amount' => 'decimal:2',
        'provider_cost' => 'decimal:4',
    ];

    public function service()
    {
        return $this->belongsTo(SmsService::class, 'service_id');
    }

    public function country()
    {
        return $this->belongsTo(SmsCountry::class, 'country_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(SmsMessage::class, 'sms_order_id');
    }

    public function paymentOrders()
    {
        return $this->hasMany(SmsPaymentOrder::class, 'sms_order_id');
    }

    public function latestPayment()
    {
        return $this->hasOne(SmsPaymentOrder::class, 'sms_order_id')->latestOfMany();
    }

    public function isPaid()
    {
        return ! empty($this->paid_at) || ! in_array($this->status, [self::STATUS_WAIT_PAY, self::STATUS_PRICE_CHANGED, self::STATUS_EXPIRED], true);
    }
}
