<?php

namespace App\Models\Sms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SmsWallet extends Model
{
    protected $fillable = [
        'user_id', 'balance', 'total_recharged', 'total_spent', 'total_refunded',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_recharged' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'total_refunded' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(SmsWalletLog::class, 'user_id', 'user_id');
    }
}
