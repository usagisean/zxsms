<?php

namespace App\Services\Sms;

use App\Models\Sms\SmsMessage;
use App\Models\Sms\SmsOrder;
use App\Models\Sms\SmsPaymentOrder;
use App\Models\Sms\SmsPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class SmsOrderService
{
    /** @var SmsPriceService */
    private $prices;

    /** @var SmsPaymentService */
    private $payments;

    /** @var HeroSmsClient */
    private $heroSms;

    /** @var SmsWalletService */
    private $wallets;

    public function __construct(SmsPriceService $prices, SmsPaymentService $payments, HeroSmsClient $heroSms, SmsWalletService $wallets)
    {
        $this->prices = $prices;
        $this->payments = $payments;
        $this->heroSms = $heroSms;
        $this->wallets = $wallets;
    }

    public function createOrder(array $input, $ip = null)
    {
        $serviceCode = (string) ($input['service_code'] ?? '');
        $countryCode = (int) ($input['country_code'] ?? 0);
        $methodCode = (string) ($input['payment_method'] ?? '');
        $displayedPrice = isset($input['displayed_price']) ? (float) $input['displayed_price'] : null;

        if ($serviceCode === '' || $countryCode <= 0) {
            throw new RuntimeException('请选择平台和国家');
        }
        if ($methodCode === '') {
            throw new RuntimeException('请选择支付方式');
        }
        if ($methodCode === 'balance' && empty($input['user_id'])) {
            throw new RuntimeException('余额支付需要先登录账号');
        }

        $cached = $this->prices->getCachedQuote($serviceCode, $countryCode);
        list($livePrice, $pricing, $providerPayload) = $this->prices->getLiveQuote($serviceCode, $countryCode);
        $salePrice = (float) $pricing['sale_price'];
        $tolerance = (float) config('sms.pricing.reprice_tolerance', 0);

        if ($displayedPrice !== null && bccomp((string) $salePrice, (string) ($displayedPrice + $tolerance), 2) === 1) {
            return [
                'changed' => true,
                'old_price' => $displayedPrice,
                'new_price' => $salePrice,
                'message' => 'HeroSMS 成本已变化，已为你重新报价，请确认后再支付。',
                'price' => $livePrice,
            ];
        }

        DB::beginTransaction();
        try {
            $order = SmsOrder::create([
                'user_id' => $input['user_id'] ?? null,
                'order_sn' => $this->makeOrderSn(),
                'token' => Str::random(48),
                'service_id' => $livePrice->service_id,
                'country_id' => $livePrice->country_id,
                'service_code' => $serviceCode,
                'country_code' => $countryCode,
                'email' => $input['email'] ?? null,
                'query_password_hash' => ! empty($input['query_password']) ? Hash::make($input['query_password']) : null,
                'cost_usd' => $pricing['cost_usd'],
                'exchange_rate' => $pricing['exchange_rate'],
                'markup_multiplier' => $pricing['markup_multiplier'],
                'fixed_fee' => $pricing['fixed_fee'],
                'min_profit' => $pricing['min_profit'],
                'min_price' => $pricing['min_price'],
                'sale_price' => $pricing['sale_price'],
                'currency' => 'CNY',
                'status' => SmsOrder::STATUS_WAIT_PAY,
                'buy_ip' => $ip,
                'expires_at' => Carbon::now()->addMinutes((int) config('sms.order.expire_minutes', 15)),
                'quote_snapshot' => [
                    'cached_price_id' => $cached ? $cached->id : null,
                    'live_price_id' => $livePrice->id,
                    'provider_payload' => $providerPayload,
                    'pricing' => $pricing,
                ],
            ]);

            $payment = null;
            if ($methodCode === 'balance') {
                $order = $this->wallets->paySmsOrder($order);
            } else {
                $payment = $this->payments->createPayment($order, $methodCode);
            }
            DB::commit();
            if ($methodCode === 'balance') {
                $this->purchaseNumber($order->fresh());
            }
            return ['changed' => false, 'order' => $order, 'payment' => $payment];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function purchaseNumber(SmsOrder $order)
    {
        if (! $order || ! $order->id) {
            throw new RuntimeException('订单不存在');
        }
        $order = SmsOrder::lockForUpdate()->where('id', $order->id)->first();
        if (! $order) {
            throw new RuntimeException('订单不存在');
        }
        if ($order->provider_activation_id || $order->phone_number) {
            return $order;
        }
        if ($order->status !== SmsOrder::STATUS_PAID) {
            return $order;
        }

        $order->status = SmsOrder::STATUS_PURCHASING;
        $order->save();

        try {
            $maxPrice = (float) $order->cost_usd + (float) config('sms.pricing.cost_tolerance_usd', 0);
            $activation = $this->heroSms->buyNumber($order->service_code, $order->country_code, $maxPrice, $order);
            $providerCost = $activation['activation_cost'];
            if ($providerCost !== null && bccomp((string) $providerCost, (string) $maxPrice, 4) === 1) {
                if (! empty($activation['activation_id'])) {
                    $this->heroSms->cancel($activation['activation_id'], $order);
                }
                $order->status = SmsOrder::STATUS_REFUND_REQUIRED;
                $order->status_note = 'HeroSMS 实际成本高于下单前确认成本，已阻止继续使用。';
                $order->provider_payload = $activation['raw'];
                $order->save();
                $this->wallets->refundSmsOrder($order, 'HeroSMS 实际成本高于确认成本，自动退回余额');
                return $order;
            }

            $order->provider_activation_id = $activation['activation_id'];
            $order->provider_currency = $activation['currency'];
            $order->provider_cost = $providerCost;
            $order->phone_number = $activation['phone_number'];
            $order->status = SmsOrder::STATUS_WAITING_CODE;
            $order->purchased_at = Carbon::now();
            $order->provider_payload = $activation['raw'];
            $order->save();
            return $order;
        } catch (\Throwable $e) {
            $note = $e->getMessage();
            $order->status = stripos($note, 'NO_NUMBERS') !== false ? SmsOrder::STATUS_PROVIDER_NO_STOCK : SmsOrder::STATUS_REFUND_REQUIRED;
            $order->status_note = mb_substr($note, 0, 250);
            $order->save();
            $this->wallets->refundSmsOrder($order, 'HeroSMS 取号失败，自动退回余额');
            return $order;
        }
    }

    public function pollCode(SmsOrder $order, $force = false)
    {
        if (! $order->provider_activation_id || $order->status !== SmsOrder::STATUS_WAITING_CODE) {
            return $order;
        }
        if (! $force && $order->last_polled_at && $order->last_polled_at->gt(Carbon::now()->subSeconds((int) config('sms.order.poll_interval_seconds', 8)))) {
            return $order;
        }

        $status = $this->heroSms->getStatus($order->provider_activation_id, $order);
        $order->last_polled_at = Carbon::now();

        if (! empty($status['has_message'])) {
            $order->sms_code = $status['code'];
            $order->sms_text = $status['text'];
            $order->status = SmsOrder::STATUS_COMPLETED;
            $order->code_received_at = Carbon::now();
            $order->save();

            SmsMessage::create([
                'sms_order_id' => $order->id,
                'provider_activation_id' => $order->provider_activation_id,
                'type' => $status['type'] ?? 'sms',
                'code' => $status['code'] ?? null,
                'text' => $status['text'] ?? null,
                'received_at' => Carbon::now(),
                'raw' => $status['raw'] ?? null,
            ]);

            try {
                $this->heroSms->complete($order->provider_activation_id, $order);
            } catch (\Throwable $e) {
                // 完成状态上报失败不影响用户拿码。
            }
        } else {
            if (! empty($status['cancelled'])) {
                $order->status = $order->wallet_paid_at ? SmsOrder::STATUS_REFUNDED : SmsOrder::STATUS_REFUND_REQUIRED;
                $order->status_note = $order->wallet_paid_at ? 'HeroSMS 返回激活已取消，余额已退回。' : 'HeroSMS 返回激活已取消，需人工处理退款。';
                $order->save();
                $this->wallets->refundSmsOrder($order, 'HeroSMS 激活取消，自动退回余额');
                return $order->fresh(['service', 'country', 'latestPayment']);
            }
            $order->save();
        }

        return $order->fresh(['service', 'country', 'latestPayment']);
    }

    public function cancelOrder(SmsOrder $order)
    {
        if ($order->provider_activation_id && in_array($order->status, [SmsOrder::STATUS_WAITING_CODE, SmsOrder::STATUS_PURCHASING], true)) {
            $this->heroSms->cancel($order->provider_activation_id, $order);
        }
        $order->status = SmsOrder::STATUS_CANCELLED;
        $order->status_note = '用户取消';
        $order->save();
        $this->wallets->refundSmsOrder($order, '用户取消接码，自动退回余额');
        return $order;
    }

    public function expireUnpaidOrders()
    {
        $orders = SmsOrder::where('status', SmsOrder::STATUS_WAIT_PAY)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->get();
        foreach ($orders as $order) {
            $order->status = SmsOrder::STATUS_EXPIRED;
            $order->save();
            SmsPaymentOrder::where('sms_order_id', $order->id)
                ->where('status', SmsPaymentOrder::STATUS_PENDING)
                ->update(['status' => SmsPaymentOrder::STATUS_EXPIRED]);
        }
        return $orders->count();
    }

    public function pollWaitingOrders($limit = 20)
    {
        $this->expireWaitingCodeOrders();

        $orders = SmsOrder::where('status', SmsOrder::STATUS_WAITING_CODE)
            ->where(function ($query) {
                $query->whereNull('last_polled_at')
                    ->orWhere('last_polled_at', '<', Carbon::now()->subSeconds((int) config('sms.order.poll_interval_seconds', 8)));
            })
            ->orderBy('updated_at')
            ->take($limit)
            ->get();

        foreach ($orders as $order) {
            $this->pollCode($order, true);
        }
        return $orders->count();
    }

    public function expireWaitingCodeOrders()
    {
        $timeout = (int) config('sms.order.poll_timeout_minutes', 20);
        $orders = SmsOrder::where('status', SmsOrder::STATUS_WAITING_CODE)
            ->whereNotNull('purchased_at')
            ->where('purchased_at', '<', Carbon::now()->subMinutes($timeout))
            ->take(50)
            ->get();

        foreach ($orders as $order) {
            try {
                if ($order->provider_activation_id) {
                    $this->heroSms->cancel($order->provider_activation_id, $order);
                }
            } catch (\Throwable $e) {
                // HeroSMS 取消失败不影响本站给用户退余额。
            }
            $order->status = $order->wallet_paid_at ? SmsOrder::STATUS_REFUNDED : SmsOrder::STATUS_REFUND_REQUIRED;
            $order->status_note = $order->wallet_paid_at ? ($timeout . ' 分钟内未收到验证码，已自动退回余额。') : ($timeout . ' 分钟内未收到验证码，需人工处理退款。');
            $order->save();
            $this->wallets->refundSmsOrder($order, '超时未收到验证码，自动退回余额');
        }

        return $orders->count();
    }

    public function findForQuery($orderSn = null, $email = null, $password = null)
    {
        $query = SmsOrder::with(['service', 'country', 'latestPayment']);
        if ($orderSn) {
            $query->where('order_sn', $orderSn);
        } elseif ($email) {
            $query->where('email', $email)->orderByDesc('created_at')->take(10);
        } else {
            return collect();
        }
        $orders = $query->get();
        $orders = $orders->filter(function (SmsOrder $order) use ($password) {
            if (empty($order->query_password_hash)) {
                return true;
            }
            return ! empty($password) && Hash::check($password, $order->query_password_hash);
        })->values();
        return $orders;
    }

    private function makeOrderSn()
    {
        do {
            $sn = 'SMS' . date('YmdHis') . strtoupper(Str::random(6));
        } while (SmsOrder::where('order_sn', $sn)->exists());
        return $sn;
    }
}
