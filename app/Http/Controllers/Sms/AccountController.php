<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsOrder;
use App\Models\Sms\SmsRechargeOrder;
use App\Models\Sms\SmsWalletLog;
use App\Services\Sms\SmsWalletService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function numbers(Request $request, SmsWalletService $wallets)
    {
        $orders = SmsOrder::with(['service', 'country', 'latestPayment'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $wallet = $wallets->wallet($request->user());
        $recharges = SmsRechargeOrder::where('user_id', $request->user()->id)->orderByDesc('created_at')->take(6)->get();
        $logs = SmsWalletLog::where('user_id', $request->user()->id)->orderByDesc('created_at')->take(10)->get();

        return view('sms.account.numbers', compact('orders', 'wallet', 'recharges', 'logs'));
    }
}
