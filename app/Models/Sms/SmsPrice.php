<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Model;

class SmsPrice extends Model
{
    protected $fillable = [
        'service_id', 'country_id', 'provider_service_code', 'provider_country_id',
        'operator', 'cost_usd', 'stock_count', 'sale_price', 'is_available',
        'synced_at', 'raw', 'title', 'description', 'base_sold_count', 'max_quantity',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'raw' => 'array',
        'synced_at' => 'datetime',
        'cost_usd' => 'decimal:4',
        'sale_price' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(SmsService::class, 'service_id');
    }

    public function country()
    {
        return $this->belongsTo(SmsCountry::class, 'country_id');
    }
}
