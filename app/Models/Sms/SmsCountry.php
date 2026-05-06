<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsCountry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'provider_id', 'name', 'name_en', 'name_cn', 'provider_visible',
        'is_enabled', 'supports_retry', 'markup_multiplier', 'fixed_fee',
        'min_profit', 'min_price', 'sort_order', 'raw',
    ];

    protected $casts = [
        'provider_visible' => 'boolean',
        'is_enabled' => 'boolean',
        'supports_retry' => 'boolean',
        'raw' => 'array',
        'markup_multiplier' => 'decimal:4',
        'fixed_fee' => 'decimal:2',
        'min_profit' => 'decimal:2',
        'min_price' => 'decimal:2',
    ];

    public function prices()
    {
        return $this->hasMany(SmsPrice::class, 'country_id');
    }
}
