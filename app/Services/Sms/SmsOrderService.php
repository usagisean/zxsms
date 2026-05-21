<?php

namespace App\Services\Sms;

use App\Models\Sms\SmsInventoryCard;
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

    /** @var InventorySmsClient */
    private $inventorySms;

    public function __construct(SmsPriceService $prices, SmsPaymentService $payments, HeroSmsClient $heroSms, SmsWalletService $wallets, InventorySmsClient $inventorySms)
    {
        $this->prices = $prices;
        $this->payments = $payments;
        $this->heroSms = $heroSms;
        $this->wallets = $wallets;
        $this->inventorySms = $inventorySms;
    }

    public function createBatchOrders(array $input, $ip = null)
    {
        $quantity = (int) ($input['quantity'] ?? 1);
        $methodCode = (string) ($input['payment_method'] ?? '');
        
        if ($quantity < 1 || $quantity > 50) {
            throw new RuntimeException('单次购买数量必须在 1 到 50 之间');
        }
        if ($quantity > 1 && $methodCode !== 'balance') {
            throw new RuntimeException('批量购买目前仅支持使用账户余额支付，请先充值');
        }

        if ($quantity === 1) {
            $res = $this->createOrder($input, $ip);
            if (!empty($res['changed'])) return $res;
            return ['changed' => false, 'orders' => collect([$res['order']])];
        }

        $results = [];
        for ($i = 0; $i < $quantity; $i++) {
            try {
                $res = $this->createOrder($input, $ip);
                if (!empty($res['changed'])) {
                    if (empty($results)) return $res;
                    break;
                }
                $results[] = $res['order'];
            } catch (\Throwable $e) {
                if (empty($results)) throw $e;
                break;
            }
        }
        return ['changed' => false, 'orders' => collect($results)];
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
        if ($this->activeProvider() === 'inventory') {
            if (empty($input['user_id'])) {
                throw new RuntimeException('请先登录账号并充值余额后再购买。');
            }
            $methodCode = 'balance';
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
                'message' => $this->activeProvider() === 'inventory' ? '库存售价已变化，已为你重新报价，请确认后再购买。' : 'HeroSMS 成本已变化，已为你重新报价，请确认后再支付。',
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
        if ($this->activeProvider() === 'inventory') {
            return $this->purchaseInventoryNumber($order);
        }

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
        if ($this->isInventoryOrder($order)) {
            if (! in_array($order->status, [SmsOrder::STATUS_WAITING_CODE, SmsOrder::STATUS_COMPLETED], true)) {
                return $order;
            }
            return $this->pollInventoryCode($order, $force);
        }
        if (! $order->provider_activation_id || $order->status !== SmsOrder::STATUS_WAITING_CODE) {
            return $order;
        }
        if (! $force && $order->last_polled_at && $order->last_polled_at->gt(Carbon::now()->subSeconds((int) config('sms.order.poll_interval_seconds', 8)))) {
            return $order;
        }

        $status = $this->heroSms->getStatus($order->provider_activation_id, $order);
        $order->last_polled_at = Carbon::now();

        if (! empty($status['has_message'])) {
            $sameMessage = (string) $order->sms_code === (string) ($status['code'] ?? '')
                && (string) $order->sms_text === (string) ($status['text'] ?? '');
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
        if ($this->isInventoryOrder($order) && $order->phone_number) {
            throw new RuntimeException('号码已发货，不能取消退款；可以继续在订单页等待或刷新短信。');
        }
        if ($order->provider_activation_id && ! $this->isInventoryOrder($order) && in_array($order->status, [SmsOrder::STATUS_WAITING_CODE, SmsOrder::STATUS_PURCHASING], true)) {
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
                $query->whereNull('provider_activation_id')
                    ->orWhere('provider_activation_id', 'not like', 'inventory:%');
            })
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
            ->where(function ($query) {
                $query->whereNull('provider_activation_id')
                    ->orWhere('provider_activation_id', 'not like', 'inventory:%');
            })
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

    public function findForQuery($orderSn = null, $email = null, $password = null, $user = null)
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
        $orders = $orders->filter(function (SmsOrder $order) use ($password, $user, $orderSn) {
            if ($user && (int) $order->user_id === (int) $user->id) {
                return true;
            }
            if (empty($order->query_password_hash)) {
                return ! empty($orderSn);
            }
            return ! empty($password) && Hash::check($password, $order->query_password_hash);
        })->values();
        return $orders;
    }


    private function purchaseInventoryNumber(SmsOrder $order)
    {
        if (! $order || ! $order->id) {
            throw new RuntimeException('订单不存在');
        }

        DB::beginTransaction();
        try {
            $order = SmsOrder::lockForUpdate()->where('id', $order->id)->first();
            if (! $order) {
                throw new RuntimeException('订单不存在');
            }
            if ($order->provider_activation_id || $order->phone_number) {
                DB::commit();
                return $order;
            }
            if ($order->status !== SmsOrder::STATUS_PAID) {
                DB::commit();
                return $order;
            }

            $order->status = SmsOrder::STATUS_PURCHASING;
            $order->save();

            $card = SmsInventoryCard::where('service_code', $order->service_code)
                ->where('country_code', (int) $order->country_code)
                ->where('status', SmsInventoryCard::STATUS_AVAILABLE)
                ->where(function ($query) {
                    $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $this->inventoryMinValidityDate());
                })
                ->orderBy('sale_price')
                ->lockForUpdate()
                ->first();

            if (! $card) {
                $order->status = SmsOrder::STATUS_PROVIDER_NO_STOCK;
                $order->status_note = '当前商品库存不足，已自动退回余额。';
                $order->save();
                DB::commit();
                try {
                    $this->wallets->refundSmsOrder($order->fresh(), '库存不足，自动退回余额');
                } catch (\Throwable $e) {
                    $order->status = SmsOrder::STATUS_REFUND_REQUIRED;
                    $order->status_note = '库存不足，但自动退余额失败，请人工处理：' . mb_substr($e->getMessage(), 0, 160);
                    $order->save();
                }
                try {
                    app(SmsPriceService::class)->syncInventoryCatalog($order->service_code, $order->country_code);
                } catch (\Throwable $e) {
                    // 刷新前台库存失败不影响本次订单退款结果。
                }
                return $order->fresh(['service', 'country', 'latestPayment']);
            }

            $card->status = SmsInventoryCard::STATUS_SOLD;
            $card->user_id = $order->user_id;
            $card->sms_order_id = $order->id;
            $card->sold_at = Carbon::now();
            $card->save();

            $order->provider_activation_id = 'inventory:' . $card->id;
            $order->provider_currency = 'CNY';
            $order->provider_cost = (float) $card->cost_cny;
            $order->phone_number = $card->phone_number;
            $order->status = SmsOrder::STATUS_WAITING_CODE;
            $order->purchased_at = Carbon::now();
            if ($card->valid_until) {
                $order->expires_at = $card->valid_until;
            }
            $order->provider_payload = [
                'provider' => 'inventory',
                'inventory_card_id' => $card->id,
                'cdk_code' => $card->cdk_code,
                'valid_until' => optional($card->valid_until)->toDateTimeString(),
            ];
            $order->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if (isset($order) && $order instanceof SmsOrder) {
                $order->status = SmsOrder::STATUS_REFUND_REQUIRED;
                $order->status_note = mb_substr($e->getMessage(), 0, 250);
                $order->save();
                $this->wallets->refundSmsOrder($order, '发货失败，自动退回余额');
                return $order;
            }
            throw $e;
        }

        try {
            app(SmsPriceService::class)->syncInventoryCatalog($order->service_code, $order->country_code);
        } catch (\Throwable $e) {
            // 发货已经完成，刷新商品缓存失败时不能把成功订单改成退款。
        }

        return $order->fresh(['service', 'country', 'latestPayment']);
    }

    private function pollInventoryCode(SmsOrder $order, $force = false)
    {
        if (! $force && $order->last_polled_at && $order->last_polled_at->gt(Carbon::now()->subSeconds((int) config('sms.order.poll_interval_seconds', 8)))) {
            return $order;
        }

        $card = $this->inventoryCardForOrder($order);
        if (! $card) {
            $order->last_polled_at = Carbon::now();
            $order->status_note = '库存记录不存在，请联系人工处理。';
            $order->save();
            return $order->fresh(['service', 'country', 'latestPayment']);
        }

        if ($card->valid_until && $card->valid_until->isPast()) {
            $card->status = SmsInventoryCard::STATUS_EXPIRED;
            $card->save();
            $order->status = SmsOrder::STATUS_EXPIRED;
            $order->status_note = '号码有效期已结束。';
            $order->last_polled_at = Carbon::now();
            $order->save();
            return $order->fresh(['service', 'country', 'latestPayment']);
        }

        $status = $this->inventorySms->getMessage($card, $order);
        $now = Carbon::now();
        $order->last_polled_at = $now;
        $card->last_polled_at = $now;

        if (! empty($status['has_message'])) {
            $order->sms_code = $status['code'];
            $order->sms_text = $status['text'];
            $order->status = SmsOrder::STATUS_COMPLETED;
            $order->code_received_at = $now;
            $order->save();

            $card->sms_code = $status['code'];
            $card->sms_text = $status['text'];
            $card->raw = $status['raw'] ?? null;
            $card->save();

            if (! $sameMessage) {
                SmsMessage::create([
                    'sms_order_id' => $order->id,
                    'provider_activation_id' => $order->provider_activation_id,
                    'type' => $status['type'] ?? 'sms',
                    'code' => $status['code'] ?? null,
                    'text' => $status['text'] ?? null,
                    'received_at' => $now,
                    'raw' => $status['raw'] ?? null,
                ]);
            }
        } else {
            $card->save();
            $order->save();
        }

        return $order->fresh(['service', 'country', 'latestPayment']);
    }

    private function inventoryCardForOrder(SmsOrder $order)
    {
        if (! $this->isInventoryOrder($order)) {
            return null;
        }
        $id = (int) substr((string) $order->provider_activation_id, strlen('inventory:'));
        if ($id <= 0) {
            return null;
        }
        return SmsInventoryCard::where('id', $id)->where('sms_order_id', $order->id)->first();
    }

    private function isInventoryOrder(SmsOrder $order)
    {
        return strpos((string) $order->provider_activation_id, 'inventory:') === 0;
    }

    private function activeProvider()
    {
        return (string) app(SmsSettingService::class)->get('sms_provider', config('sms.provider', 'inventory'));
    }

    private function inventoryMinValidityDate()
    {
        $days = max(1, (int) app(SmsSettingService::class)->get('product_min_validity_days', 30));
        return Carbon::today()->addDays($days)->toDateString();
    }

    private function makeOrderSn()
    {
        do {
            $sn = 'SMS' . date('YmdHis') . strtoupper(Str::random(6));
        } while (SmsOrder::where('order_sn', $sn)->exists());
        return $sn;
    }
}
