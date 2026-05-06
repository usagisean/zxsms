<?php

namespace App\Services\Sms;

use App\Models\Sms\SmsOrder;
use App\Models\Sms\SmsRechargeOrder;
use App\Models\Sms\SmsWallet;
use App\Models\Sms\SmsWalletLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmsWalletService
{
    public function wallet(User $user)
    {
        return SmsWallet::firstOrCreate(['user_id' => $user->id], [
            'balance' => 0,
            'total_recharged' => 0,
            'total_spent' => 0,
            'total_refunded' => 0,
        ]);
    }

    public function balance(User $user)
    {
        return (float) $this->wallet($user)->balance;
    }

    public function creditRecharge(SmsRechargeOrder $recharge)
    {
        if ($recharge->status !== SmsRechargeOrder::STATUS_PAID) {
            throw new RuntimeException('充值订单未支付，不能入账');
        }

        return DB::transaction(function () use ($recharge) {
            $exists = SmsWalletLog::where('recharge_order_id', $recharge->id)
                ->where('type', SmsWalletLog::TYPE_RECHARGE)
                ->exists();
            if ($exists) {
                return $this->wallet($recharge->user)->fresh();
            }

            $wallet = SmsWallet::where('user_id', $recharge->user_id)->lockForUpdate()->first();
            if (! $wallet) {
                $wallet = SmsWallet::create(['user_id' => $recharge->user_id]);
                $wallet = SmsWallet::where('user_id', $recharge->user_id)->lockForUpdate()->first();
            }

            $before = (float) $wallet->balance;
            $amount = (float) $recharge->total_amount;
            $wallet->balance = round($before + $amount, 2);
            $wallet->total_recharged = round((float) $wallet->total_recharged + $amount, 2);
            $wallet->save();

            SmsWalletLog::create([
                'user_id' => $recharge->user_id,
                'recharge_order_id' => $recharge->id,
                'type' => SmsWalletLog::TYPE_RECHARGE,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => (float) $wallet->balance,
                'remark' => '充值入账：' . $recharge->recharge_sn,
                'meta' => ['amount' => (float) $recharge->amount, 'bonus_amount' => (float) $recharge->bonus_amount],
            ]);

            return $wallet->fresh();
        });
    }

    public function paySmsOrder(SmsOrder $order)
    {
        if (! $order->user_id) {
            throw new RuntimeException('余额支付需要先登录');
        }
        if ((float) $order->wallet_amount > 0 || $order->wallet_paid_at) {
            return $order;
        }

        return DB::transaction(function () use ($order) {
            $order = SmsOrder::lockForUpdate()->where('id', $order->id)->firstOrFail();
            if ((float) $order->wallet_amount > 0 || $order->wallet_paid_at) {
                return $order;
            }

            $wallet = SmsWallet::where('user_id', $order->user_id)->lockForUpdate()->first();
            if (! $wallet) {
                $wallet = SmsWallet::create(['user_id' => $order->user_id]);
                $wallet = SmsWallet::where('user_id', $order->user_id)->lockForUpdate()->first();
            }

            $amount = (float) $order->sale_price;
            $before = (float) $wallet->balance;
            if (bccomp((string) $before, (string) $amount, 2) < 0) {
                throw new RuntimeException('余额不足，请先充值。当前余额 ¥' . number_format($before, 2) . '，本单需要 ¥' . number_format($amount, 2));
            }

            $wallet->balance = round($before - $amount, 2);
            $wallet->total_spent = round((float) $wallet->total_spent + $amount, 2);
            $wallet->save();

            $now = now();
            $order->wallet_amount = $amount;
            $order->wallet_paid_at = $now;
            $order->paid_at = $now;
            $order->status = SmsOrder::STATUS_PAID;
            $order->save();

            SmsWalletLog::create([
                'user_id' => $order->user_id,
                'sms_order_id' => $order->id,
                'type' => SmsWalletLog::TYPE_SPEND,
                'amount' => -$amount,
                'balance_before' => $before,
                'balance_after' => (float) $wallet->balance,
                'remark' => '接码扣款：' . $order->order_sn,
                'meta' => ['service' => $order->service_code, 'country' => $order->country_code],
            ]);

            return $order->fresh();
        });
    }

    public function refundSmsOrder(SmsOrder $order, $reason = '接码失败自动退回余额')
    {
        if (! $order->user_id || ! $order->wallet_paid_at || $order->wallet_refunded_at || (float) $order->wallet_amount <= 0) {
            return $order;
        }

        return DB::transaction(function () use ($order, $reason) {
            $order = SmsOrder::lockForUpdate()->where('id', $order->id)->firstOrFail();
            if (! $order->user_id || ! $order->wallet_paid_at || $order->wallet_refunded_at || (float) $order->wallet_amount <= 0) {
                return $order;
            }

            $wallet = SmsWallet::where('user_id', $order->user_id)->lockForUpdate()->first();
            if (! $wallet) {
                $wallet = SmsWallet::create(['user_id' => $order->user_id]);
                $wallet = SmsWallet::where('user_id', $order->user_id)->lockForUpdate()->first();
            }

            $amount = (float) $order->wallet_amount;
            $before = (float) $wallet->balance;
            $wallet->balance = round($before + $amount, 2);
            $wallet->total_refunded = round((float) $wallet->total_refunded + $amount, 2);
            $wallet->save();

            $order->wallet_refunded_at = now();
            $order->wallet_refund_reason = mb_substr((string) $reason, 0, 250);
            if (! in_array($order->status, [SmsOrder::STATUS_COMPLETED, SmsOrder::STATUS_REFUNDED], true)) {
                $order->status = SmsOrder::STATUS_REFUNDED;
            }
            $order->save();

            SmsWalletLog::create([
                'user_id' => $order->user_id,
                'sms_order_id' => $order->id,
                'type' => SmsWalletLog::TYPE_REFUND,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => (float) $wallet->balance,
                'remark' => $reason . '：' . $order->order_sn,
                'meta' => ['service' => $order->service_code, 'country' => $order->country_code],
            ]);

            return $order->fresh();
        });
    }
}
