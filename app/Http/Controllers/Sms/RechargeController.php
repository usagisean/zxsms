<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsRechargeOrder;
use App\Services\Sms\SmsPaymentService;
use App\Services\Sms\SmsRechargeService;
use App\Services\Sms\SmsWalletService;
use Illuminate\Http\Request;
use RuntimeException;

class RechargeController extends Controller
{
    /** @var SmsRechargeService */
    private $recharges;

    /** @var SmsPaymentService */
    private $payments;

    /** @var SmsWalletService */
    private $wallets;

    public function __construct(SmsRechargeService $recharges, SmsPaymentService $payments, SmsWalletService $wallets)
    {
        $this->middleware('auth');
        $this->recharges = $recharges;
        $this->payments = $payments;
        $this->wallets = $wallets;
    }

    public function index(Request $request)
    {
        return view('sms.recharge.index', [
            'plans' => $this->recharges->plans(),
            'methods' => $this->payments->enabledMethods(),
            'wallet' => $this->wallets->wallet($request->user()),
            'orders' => SmsRechargeOrder::where('user_id', $request->user()->id)->orderByDesc('created_at')->take(8)->get(),
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:sms_recharge_plans,id'],
            'payment_method' => ['required', 'string', 'max:60'],
        ]);

        try {
            $recharge = $this->recharges->create($request->user(), $data['plan_id'], $data['payment_method']);
            return redirect()->route('sms.recharge.show', ['token' => $recharge->token]);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['recharge' => $e->getMessage()]);
        }
    }

    public function show(Request $request, $token)
    {
        $recharge = SmsRechargeOrder::with('plan')->where('token', $token)->where('user_id', $request->user()->id)->firstOrFail();
        return view('sms.recharge.show', compact('recharge'));
    }
}
