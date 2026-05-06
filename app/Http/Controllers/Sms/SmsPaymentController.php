<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsPaymentOrder;
use App\Models\Sms\SmsRechargeOrder;
use App\Services\Sms\SmsPaymentService;
use Illuminate\Http\Request;
use RuntimeException;

class SmsPaymentController extends Controller
{
    /** @var SmsPaymentService */
    private $payments;

    public function __construct(SmsPaymentService $payments)
    {
        $this->payments = $payments;
    }

    public function gateway($methodCode, $paymentSn)
    {
        $payment = SmsPaymentOrder::with('order')
            ->where('payment_sn', $paymentSn)
            ->where('method_code', $methodCode)
            ->firstOrFail();
        try {
            return $this->payments->gateway($payment);
        } catch (RuntimeException $e) {
            return response()->view('sms.error', ['message' => $e->getMessage()], 400);
        }
    }

    public function rechargeGateway($paymentSn)
    {
        $recharge = SmsRechargeOrder::where('payment_sn', $paymentSn)->firstOrFail();
        if (! auth()->check() || auth()->id() !== (int) $recharge->user_id) {
            abort(403);
        }
        try {
            return $this->payments->rechargeGateway($recharge);
        } catch (RuntimeException $e) {
            return response()->view('sms.error', ['message' => $e->getMessage()], 400);
        }
    }

    public function yipayNotify(Request $request)
    {
        return response($this->payments->handleYipayNotify($request));
    }

    public function yipayReturn(Request $request)
    {
        return $this->redirectPayment($request->get('payment_sn'));
    }

    public function epusdtNotify(Request $request)
    {
        return response($this->payments->handleEpusdtNotify($request));
    }

    public function epusdtReturn(Request $request)
    {
        return $this->redirectPayment($request->get('payment_sn'));
    }

    private function redirectPayment($paymentSn)
    {
        $payment = SmsPaymentOrder::with('order')->where('payment_sn', $paymentSn)->first();
        if ($payment && $payment->order) {
            return redirect()->route('sms.order.show', ['token' => $payment->order->token]);
        }
        $recharge = SmsRechargeOrder::where('payment_sn', $paymentSn)->first();
        if ($recharge) {
            return redirect()->route('sms.recharge.show', ['token' => $recharge->token]);
        }
        return redirect()->route('sms.index');
    }
}
