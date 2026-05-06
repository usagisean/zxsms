<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsHomeSlide extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'badge', 'title', 'description', 'image_url', 'card_title', 'card_value',
        'card_description', 'is_enabled', 'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
