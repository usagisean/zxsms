<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsOrder;
use App\Models\Sms\SmsPaymentOrder;
use App\Services\Sms\SmsOrderService;
use App\Services\Sms\SmsPaymentService;
use App\Services\Sms\SmsPriceService;
use App\Services\Sms\SmsWalletService;
use Illuminate\Http\Request;
use RuntimeException;

class SmsController extends Controller
{
    /** @var SmsPriceService */
    private $prices;

    /** @var SmsOrderService */
    private $orders;

    /** @var SmsPaymentService */
    private $payments;

    public function __construct(SmsPriceService $prices, SmsOrderService $orders, SmsPaymentService $payments)
    {
        $this->prices = $prices;
        $this->orders = $orders;
        $this->payments = $payments;
    }

    public function index()
    {
        $catalog = $this->prices->publicCatalog();
        $methods = $this->payments->enabledMethods();
        $wallet = request()->user() ? app(SmsWalletService::class)->wallet(request()->user()) : null;
        return view('sms.index', compact('catalog', 'methods', 'wallet'));
    }

    public function createOrder(Request $request)
    {
        $data = $request->validate([
            'service_code' => ['required', 'string', 'max:60'],
            'country_code' => ['required', 'integer'],
            'displayed_price' => ['nullable', 'numeric'],
            'payment_method' => ['required', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:190'],
            'query_password' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            if ($request->user()) {
                $data['user_id'] = $request->user()->id;
                if (empty($data['email'])) {
                    $data['email'] = $request->user()->email;
                }
            }
            $result = $this->orders->createOrder($data, $request->ip());
            if (! empty($result['changed'])) {
                return back()->withInput()->with('quote_changed', $result['message'])->with('new_price', $result['new_price']);
            }
            return redirect()->route('sms.order.show', ['token' => $result['order']->token]);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['order' => $e->getMessage()]);
        }
    }

    public function showOrder($token)
    {
        $order = SmsOrder::with(['service', 'country', 'latestPayment'])->where('token', $token)->firstOrFail();
        return view('sms.order', compact('order'));
    }

    public function orderStatus($token)
    {
        $order = SmsOrder::with(['service', 'country', 'latestPayment'])->where('token', $token)->firstOrFail();
        if ($order->status === SmsOrder::STATUS_WAITING_CODE) {
            $order = $this->orders->pollCode($order);
        } elseif ($order->status === SmsOrder::STATUS_PAID) {
            $order = $this->orders->purchaseNumber($order)->fresh(['service', 'country', 'latestPayment']);
        }

        return response()->json([
            'status' => $order->status,
            'status_text' => $this->statusText($order->status),
            'status_note' => $order->status_note,
            'phone_number' => $order->phone_number,
            'sms_code' => $order->sms_code,
            'sms_text' => $order->sms_text,
            'paid_at' => optional($order->paid_at)->toDateTimeString(),
            'purchased_at' => optional($order->purchased_at)->toDateTimeString(),
            'code_received_at' => optional($order->code_received_at)->toDateTimeString(),
        ]);
    }

    public function cancelOrder(Request $request, $token)
    {
        $order = SmsOrder::where('token', $token)->firstOrFail();
        if (in_array($order->status, [SmsOrder::STATUS_COMPLETED, SmsOrder::STATUS_CANCELLED, SmsOrder::STATUS_EXPIRED], true)) {
            return back()->withErrors(['cancel' => '当前状态不能取消']);
        }
        try {
            $this->orders->cancelOrder($order);
            return back()->with('ok', '已取消');
        } catch (\Throwable $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }
    }

    public function query()
    {
        return view('sms.query', ['orders' => null]);
    }

    public function queryPost(Request $request)
    {
        $data = $request->validate([
            'order_sn' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'query_password' => ['nullable', 'string', 'max:80'],
        ]);

        $orders = $this->orders->findForQuery($data['order_sn'] ?? null, $data['email'] ?? null, $data['query_password'] ?? null);
        return view('sms.query', compact('orders'));
    }

    public function statusText($status)
    {
        $map = [
            SmsOrder::STATUS_WAIT_PAY => '待支付',
            SmsOrder::STATUS_PAID => '已支付，准备取号',
            SmsOrder::STATUS_PURCHASING => '正在获取号码',
            SmsOrder::STATUS_WAITING_CODE => '等待验证码',
            SmsOrder::STATUS_COMPLETED => '已完成',
            SmsOrder::STATUS_PRICE_CHANGED => '价格已变化',
            SmsOrder::STATUS_PROVIDER_NO_STOCK => 'HeroSMS 无库存',
            SmsOrder::STATUS_REFUND_REQUIRED => '需人工处理/退款',
            SmsOrder::STATUS_REFUNDED => '已退回余额',
            SmsOrder::STATUS_CANCELLED => '已取消',
            SmsOrder::STATUS_EXPIRED => '已过期',
            SmsOrder::STATUS_FAILED => '失败',
        ];
        return $map[$status] ?? $status;
    }
}
