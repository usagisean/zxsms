<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'provider_code', 'name', 'is_enabled', 'is_featured', 'markup_multiplier', 'fixed_fee',
        'min_profit', 'min_price', 'sort_order', 'raw',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_featured' => 'boolean',
        'raw' => 'array',
        'markup_multiplier' => 'decimal:4',
        'fixed_fee' => 'decimal:2',
        'min_profit' => 'decimal:2',
        'min_price' => 'decimal:2',
    ];

    public function prices()
    {
        return $this->hasMany(SmsPrice::class, 'service_id');
    }
}
