<?php

namespace App\Services\Sms;

use App\Models\Sms\SmsOrder;
use App\Models\Sms\SmsPaymentOrder;
use App\Models\Sms\SmsRechargeOrder;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SmsPaymentService
{
    public function enabledMethods()
    {
        $methods = config('sms.payments', []);
        $result = [];
        $settings = app(SmsSettingService::class);
        foreach ($methods as $code => $method) {
            $method = $this->mergeSettingOverrides($code, $method, $settings);
            if (empty($method['enabled']) || ! $this->methodConfigured($method)) {
                continue;
            }
            $result[$code] = array_merge($method, ['code' => $code]);
        }
        uasort($result, function ($a, $b) {
            return (int) ($a['sort_order'] ?? 0) <=> (int) ($b['sort_order'] ?? 0);
        });
        return $result;
    }

    public function getMethod($methodCode)
    {
        $methods = $this->enabledMethods();
        if (! isset($methods[$methodCode])) {
            throw new RuntimeException('支付方式不存在或未启用');
        }
        return $methods[$methodCode];
    }

    public function createPayment(SmsOrder $order, $methodCode)
    {
        $method = $this->getMethod($methodCode);
        return SmsPaymentOrder::create([
            'sms_order_id' => $order->id,
            'payment_sn' => $this->makePaymentSn(),
            'method_code' => $methodCode,
            'driver' => $method['driver'],
            'pay_check' => $method['pay_check'],
            'amount' => $order->sale_price,
            'currency' => $order->currency,
            'status' => SmsPaymentOrder::STATUS_PENDING,
        ]);
    }

    public function gateway(SmsPaymentOrder $payment)
    {
        $payment->load('order');
        if (! $payment->order) {
            throw new RuntimeException('接码订单不存在');
        }
        if ($payment->status !== SmsPaymentOrder::STATUS_PENDING) {
            return redirect()->route('sms.order.show', ['token' => $payment->order->token]);
        }
        if ($payment->order->expires_at && $payment->order->expires_at->isPast()) {
            $payment->update(['status' => SmsPaymentOrder::STATUS_EXPIRED]);
            $payment->order->update(['status' => SmsOrder::STATUS_EXPIRED]);
            throw new RuntimeException('订单已过期，请重新下单');
        }

        $method = $this->getMethod($payment->method_code);
        if ($payment->driver === 'yipay') {
            return $this->yipayGateway($payment, $method);
        }
        if ($payment->driver === 'epusdt') {
            return $this->epusdtGateway($payment, $method);
        }
        throw new RuntimeException('暂不支持的支付驱动：' . $payment->driver);
    }

    public function rechargeGateway(SmsRechargeOrder $recharge)
    {
        if ($recharge->status !== SmsRechargeOrder::STATUS_PENDING) {
            return redirect()->route('sms.recharge.show', ['token' => $recharge->token]);
        }
        if ($recharge->expires_at && $recharge->expires_at->isPast()) {
            $recharge->update(['status' => SmsRechargeOrder::STATUS_EXPIRED]);
            throw new RuntimeException('充值订单已过期，请重新充值');
        }

        $method = $this->getMethod($recharge->method_code);
        if ($recharge->driver === 'yipay') {
            return $this->yipayRechargeGateway($recharge, $method);
        }
        if ($recharge->driver === 'epusdt') {
            return $this->epusdtRechargeGateway($recharge, $method);
        }
        throw new RuntimeException('暂不支持的支付驱动：' . $recharge->driver);
    }

    public function handleYipayNotify(Request $request)
    {
        $data = $request->all();
        $paymentSn = $data['out_trade_no'] ?? null;
        if (! $paymentSn) {
            return 'fail';
        }
        $payment = SmsPaymentOrder::with('order')->where('payment_sn', $paymentSn)->first();
        $recharge = $payment ? null : SmsRechargeOrder::where('payment_sn', $paymentSn)->first();
        if ((! $payment || ! $payment->order) && ! $recharge) {
            return 'fail';
        }
        $method = $this->getMethod($payment ? $payment->method_code : $recharge->method_code);
        if ($method['driver'] !== 'yipay') {
            return 'fail';
        }

        $sign = $this->yipaySign($data, $method['merchant_secret']);
        if (empty($data['trade_no']) || ! hash_equals((string) ($data['sign'] ?? ''), $sign)) {
            return 'fail';
        }

        try {
            if ($payment) {
                $this->completePayment($payment, (float) $data['money'], (string) $data['trade_no'], $data);
            } else {
                app(SmsRechargeService::class)->completePayment($recharge, (float) $data['money'], (string) $data['trade_no'], $data);
            }
            return 'success';
        } catch (\Throwable $e) {
            return 'fail';
        }
    }

    public function handleEpusdtNotify(Request $request)
    {
        $data = $request->all();
        $paymentSn = $data['order_id'] ?? null;
        if (! $paymentSn) {
            return 'fail';
        }
        $payment = SmsPaymentOrder::with('order')->where('payment_sn', $paymentSn)->first();
        $recharge = $payment ? null : SmsRechargeOrder::where('payment_sn', $paymentSn)->first();
        if ((! $payment || ! $payment->order) && ! $recharge) {
            return 'fail';
        }
        $method = $this->getMethod($payment ? $payment->method_code : $recharge->method_code);
        if ($method['driver'] !== 'epusdt') {
            return 'fail';
        }

        $signature = $this->epusdtSign($data, $method['merchant_secret'] ?: ($method['merchant_id'] ?? ''));
        if (! hash_equals((string) ($data['signature'] ?? ''), $signature)) {
            return 'fail';
        }
        if ((int) ($data['status'] ?? 0) !== 2) {
            return 'fail';
        }

        try {
            if ($payment) {
                $this->completePayment($payment, (float) $data['amount'], (string) ($data['trade_id'] ?? ''), $data);
            } else {
                app(SmsRechargeService::class)->completePayment($recharge, (float) $data['amount'], (string) ($data['trade_id'] ?? ''), $data);
            }
            return 'ok';
        } catch (\Throwable $e) {
            return 'fail';
        }
    }

    public function completePayment(SmsPaymentOrder $payment, $paidAmount, $tradeNo, array $notifyPayload = [])
    {
        DB::beginTransaction();
        try {
            $payment = SmsPaymentOrder::with('order')->lockForUpdate()->where('id', $payment->id)->firstOrFail();
            $order = SmsOrder::lockForUpdate()->where('id', $payment->sms_order_id)->firstOrFail();

            if ($payment->status === SmsPaymentOrder::STATUS_PAID) {
                DB::commit();
                return $order;
            }
            if (bccomp((string) $payment->amount, (string) $paidAmount, 2) !== 0) {
                throw new RuntimeException('支付金额不一致');
            }
            if ($order->status !== SmsOrder::STATUS_WAIT_PAY) {
                throw new RuntimeException('订单状态不允许支付完成：' . $order->status);
            }

            $now = Carbon::now();
            $payment->status = SmsPaymentOrder::STATUS_PAID;
            $payment->trade_no = $tradeNo;
            $payment->paid_at = $now;
            $payment->notify_payload = $this->safePayload($notifyPayload);
            $payment->save();

            $order->status = SmsOrder::STATUS_PAID;
            $order->paid_at = $now;
            $order->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // 支付确认后立刻购买号码。失败时订单会被标记为需人工处理/退款。
        app(SmsOrderService::class)->purchaseNumber($order->fresh());
        return $order->fresh();
    }



    private function methodConfigured(array $method)
    {
        if (($method['driver'] ?? '') === 'yipay') {
            return ! empty($method['merchant_id']) && ! empty($method['merchant_key']) && ! empty($method['merchant_secret']);
        }
        if (($method['driver'] ?? '') === 'epusdt') {
            return ! empty($method['endpoint_url']) && (! empty($method['merchant_secret']) || ! empty($method['merchant_id']));
        }
        return true;
    }

    private function mergeSettingOverrides($code, array $method, SmsSettingService $settings)
    {
        $map = [
            'enabled' => ['payment_' . $code . '_enabled', 'bool'],
            'merchant_id' => ['payment_' . $code . '_merchant_id', 'string'],
            'merchant_key' => ['payment_' . $code . '_merchant_key', 'string'],
            'merchant_secret' => ['payment_' . $code . '_merchant_secret', 'string'],
            'endpoint_url' => ['payment_' . $code . '_endpoint_url', 'string'],
            'pay_check' => ['payment_' . $code . '_pay_check', 'string'],
        ];
        foreach ($map as $field => $meta) {
            if ($settings->has($meta[0])) {
                $method[$field] = $settings->get($meta[0], $method[$field] ?? null);
            }
        }
        return $method;
    }

    private function yipayGateway(SmsPaymentOrder $payment, array $method)
    {
        foreach (['merchant_id', 'merchant_key', 'merchant_secret'] as $field) {
            if (empty($method[$field])) {
                throw new RuntimeException('易支付配置不完整：' . $field);
            }
        }
        $order = $payment->order;
        $parameter = [
            'pid' => $method['merchant_id'],
            'type' => $method['pay_check'],
            'out_trade_no' => $payment->payment_sn,
            'return_url' => route('sms.pay.yipay.return', ['payment_sn' => $payment->payment_sn]),
            'notify_url' => route('sms.pay.yipay.notify'),
            'name' => 'ZXAIHUB SMS 接码 ' . $order->service_code . '-' . $order->country_code,
            'money' => (float) $payment->amount,
            'sign_type' => 'MD5',
        ];
        $parameter['sign'] = $this->yipaySign($parameter, $method['merchant_secret']);
        $payment->update(['request_payload' => $this->safePayload($parameter)]);

        return $this->autoSubmitForm($method['merchant_key'], $parameter);
    }

    private function yipayRechargeGateway(SmsRechargeOrder $recharge, array $method)
    {
        foreach (['merchant_id', 'merchant_key', 'merchant_secret'] as $field) {
            if (empty($method[$field])) {
                throw new RuntimeException('易支付配置不完整：' . $field);
            }
        }
        $parameter = [
            'pid' => $method['merchant_id'],
            'type' => $method['pay_check'],
            'out_trade_no' => $recharge->payment_sn,
            'return_url' => route('sms.pay.yipay.return', ['payment_sn' => $recharge->payment_sn]),
            'notify_url' => route('sms.pay.yipay.notify'),
            'name' => 'ZXAIHUB SMS 余额充值 ' . $recharge->recharge_sn,
            'money' => (float) $recharge->amount,
            'sign_type' => 'MD5',
        ];
        $parameter['sign'] = $this->yipaySign($parameter, $method['merchant_secret']);
        $recharge->update(['request_payload' => $this->safePayload($parameter)]);

        return $this->autoSubmitForm($method['merchant_key'], $parameter);
    }

    private function epusdtGateway(SmsPaymentOrder $payment, array $method)
    {
        if (empty($method['endpoint_url'])) {
            throw new RuntimeException('Epusdt 配置不完整：endpoint_url');
        }
        $epusdtKey = $method['merchant_secret'] ?: ($method['merchant_id'] ?? null);
        if (empty($epusdtKey)) {
            throw new RuntimeException('Epusdt 配置不完整：API Key');
        }
        $parameter = [
            'trade_type' => $method['pay_check'],
            'amount' => (float) $payment->amount,
            'order_id' => $payment->payment_sn,
            'redirect_url' => route('sms.pay.epusdt.return', ['payment_sn' => $payment->payment_sn]),
            'notify_url' => route('sms.pay.epusdt.notify'),
        ];
        $parameter['signature'] = $this->epusdtSign($parameter, $epusdtKey);
        $payment->update(['request_payload' => $this->safePayload($parameter)]);

        $client = new Client(['headers' => ['Content-Type' => 'application/json'], 'http_errors' => false, 'timeout' => 15]);
        $response = $client->post($method['endpoint_url'], ['body' => json_encode($parameter, JSON_UNESCAPED_UNICODE)]);
        $body = json_decode((string) $response->getBody(), true);
        if (! is_array($body) || ! isset($body['status_code']) || (int) $body['status_code'] !== 200) {
            $message = is_array($body) ? ($body['message'] ?? json_encode($body, JSON_UNESCAPED_UNICODE)) : '接口无响应';
            throw new RuntimeException('Epusdt 创建支付失败：' . $message);
        }
        if (empty($body['data']['payment_url'])) {
            throw new RuntimeException('Epusdt 未返回支付地址');
        }
        return redirect()->away($body['data']['payment_url']);
    }

    private function epusdtRechargeGateway(SmsRechargeOrder $recharge, array $method)
    {
        if (empty($method['endpoint_url'])) {
            throw new RuntimeException('Epusdt 配置不完整：endpoint_url');
        }
        $epusdtKey = $method['merchant_secret'] ?: ($method['merchant_id'] ?? null);
        if (empty($epusdtKey)) {
            throw new RuntimeException('Epusdt 配置不完整：API Key');
        }
        $parameter = [
            'trade_type' => $method['pay_check'],
            'amount' => (float) $recharge->amount,
            'order_id' => $recharge->payment_sn,
            'redirect_url' => route('sms.pay.epusdt.return', ['payment_sn' => $recharge->payment_sn]),
            'notify_url' => route('sms.pay.epusdt.notify'),
        ];
        $parameter['signature'] = $this->epusdtSign($parameter, $epusdtKey);
        $recharge->update(['request_payload' => $this->safePayload($parameter)]);

        $client = new Client(['headers' => ['Content-Type' => 'application/json'], 'http_errors' => false, 'timeout' => 15]);
        $response = $client->post($method['endpoint_url'], ['body' => json_encode($parameter, JSON_UNESCAPED_UNICODE)]);
        $body = json_decode((string) $response->getBody(), true);
        if (! is_array($body) || ! isset($body['status_code']) || (int) $body['status_code'] !== 200) {
            $message = is_array($body) ? ($body['message'] ?? json_encode($body, JSON_UNESCAPED_UNICODE)) : '接口无响应';
            throw new RuntimeException('Epusdt 创建支付失败：' . $message);
        }
        if (empty($body['data']['payment_url'])) {
            throw new RuntimeException('Epusdt 未返回支付地址');
        }
        return redirect()->away($body['data']['payment_url']);
    }

    private function autoSubmitForm($action, array $parameter)
    {
        $html = "<form id='sms-yipay-submit' action='" . e($action) . "' method='get'>";
        foreach ($parameter as $key => $value) {
            $html .= "<input type='hidden' name='" . e($key) . "' value='" . e($value) . "'>";
        }
        $html .= "<noscript><button type='submit'>继续支付</button></noscript></form>";
        $html .= "<script>document.getElementById('sms-yipay-submit').submit();</script>";
        return response($html);
    }

    private function yipaySign(array $data, $key)
    {
        ksort($data);
        $sign = '';
        foreach ($data as $k => $v) {
            if ($k === 'sign' || $k === 'sign_type' || $v === '' || $v === null) {
                continue;
            }
            $sign .= ($sign === '' ? '' : '&') . $k . '=' . $v;
        }
        return md5($sign . $key);
    }

    private function epusdtSign(array $data, $key)
    {
        ksort($data);
        $sign = '';
        foreach ($data as $k => $v) {
            if ($k === 'signature' || $v === '' || $v === null) {
                continue;
            }
            $sign .= ($sign === '' ? '' : '&') . $k . '=' . $v;
        }
        return md5($sign . $key);
    }

    private function makePaymentSn()
    {
        do {
            $sn = 'SP' . date('YmdHis') . strtoupper(Str::random(8));
        } while (SmsPaymentOrder::where('payment_sn', $sn)->exists());
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
