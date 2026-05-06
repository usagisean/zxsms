<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Model;

class SmsSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'is_secret', 'group'];

    protected $casts = [
        'is_secret' => 'boolean',
    ];
}
