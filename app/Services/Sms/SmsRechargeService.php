<?php

namespace App\Services\Sms;

use App\Models\Sms\SmsRechargeOrder;
use App\Models\Sms\SmsRechargePlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SmsRechargeService
{
    /** @var SmsPaymentService */
    private $payments;

    /** @var SmsWalletService */
    private $wallets;

    public function __construct(SmsPaymentService $payments, SmsWalletService $wallets)
    {
        $this->payments = $payments;
        $this->wallets = $wallets;
    }

    public function plans()
    {
        if (SmsRechargePlan::count() === 0) {
            foreach (config('sms.recharge.default_plans', []) as $plan) {
                SmsRechargePlan::create($plan + ['is_enabled' => true]);
            }
        }

        return SmsRechargePlan::where('is_enabled', true)->orderBy('sort_order')->orderBy('amount')->get();
    }

    public function create(User $user, $planId, $methodCode)
    {
        $plan = SmsRechargePlan::where('is_enabled', true)->findOrFail($planId);
        $method = $this->payments->getMethod($methodCode);

        return DB::transaction(function () use ($user, $plan, $methodCode, $method) {
            if (config('sms.recharge.reuse_pending', true)) {
                // 已经拉起过第三方支付的待支付订单，过期前优先复用，避免反复创建/拉起新扫码单触发风控。
                $openedPending = SmsRechargeOrder::where('user_id', $user->id)
                    ->where('status', SmsRechargeOrder::STATUS_PENDING)
                    ->whereNotNull('request_payload')
                    ->where(function ($query) {
                        $query->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
                    })
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($openedPending) {
                    $openedPending->reused_pending = true;
                    return $openedPending;
                }

                // 未拉起支付时，同一用户、同一档位、同一支付方式的待支付单也复用。
                $matchingPending = SmsRechargeOrder::where('user_id', $user->id)
                    ->where('plan_id', $plan->id)
                    ->where('method_code', $methodCode)
                    ->where('status', SmsRechargeOrder::STATUS_PENDING)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
                    })
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($matchingPending) {
                    $matchingPending->reused_pending = true;
                    return $matchingPending;
                }
            }

            return SmsRechargeOrder::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'recharge_sn' => $this->makeRechargeSn(),
                'token' => Str::random(48),
                'payment_sn' => $this->makePaymentSn(),
                'method_code' => $methodCode,
                'driver' => $method['driver'],
                'pay_check' => $method['pay_check'],
                'amount' => $plan->amount,
                'bonus_amount' => $plan->bonus_amount,
                'total_amount' => round((float) $plan->amount + (float) $plan->bonus_amount, 2),
                'currency' => 'CNY',
                'status' => SmsRechargeOrder::STATUS_PENDING,
                'expires_at' => Carbon::now()->addMinutes((int) config('sms.recharge.expire_minutes', 15)),
            ]);
        });
    }

    public function completePayment(SmsRechargeOrder $recharge, $paidAmount, $tradeNo, array $notifyPayload = [])
    {
        DB::beginTransaction();
        try {
            $recharge = SmsRechargeOrder::with('user')->lockForUpdate()->where('id', $recharge->id)->firstOrFail();
            if ($recharge->status === SmsRechargeOrder::STATUS_PAID) {
                DB::commit();
                return $recharge;
            }
            if (bccomp((string) $recharge->amount, (string) $paidAmount, 2) !== 0) {
                throw new RuntimeException('充值支付金额不一致');
            }
            if ($recharge->status !== SmsRechargeOrder::STATUS_PENDING) {
                throw new RuntimeException('充值订单状态不允许支付完成：' . $recharge->status);
            }
            $recharge->status = SmsRechargeOrder::STATUS_PAID;
            $recharge->trade_no = $tradeNo;
            $recharge->paid_at = now();
            $recharge->notify_payload = $this->safePayload($notifyPayload);
            $recharge->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->wallets->creditRecharge($recharge->fresh('user'));
        return $recharge->fresh('user');
    }

    public function expirePending()
    {
        return SmsRechargeOrder::where('status', SmsRechargeOrder::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->update(['status' => SmsRechargeOrder::STATUS_EXPIRED]);
    }

    private function makeRechargeSn()
    {
        do {
            $sn = 'RC' . date('YmdHis') . strtoupper(Str::random(6));
        } while (SmsRechargeOrder::where('recharge_sn', $sn)->exists());
        return $sn;
    }

    private function makePaymentSn()
    {
        do {
            $sn = 'SR' . date('YmdHis') . strtoupper(Str::random(8));
        } while (SmsRechargeOrder::where('payment_sn', $sn)->exists());
        return $sn;
    }

    private function safePayload(array $payload)
    {
        foreach (['sign', 'signature'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key])) {
                $payload[$key] = substr($payload[$key], 0, 6) . '***';
            }
        }
        return $payload;
    }
}
