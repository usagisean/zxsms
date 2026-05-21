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
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        try {
            if ($request->user()) {
                $data['user_id'] = $request->user()->id;
                if (empty($data['email'])) {
                    $data['email'] = $request->user()->email;
                }
            }
            $result = $this->orders->createBatchOrders($data, $request->ip());
            if (! empty($result['changed'])) {
                return back()->withInput()->with('quote_changed', $result['message'])->with('new_price', $result['new_price']);
            }
            
            $orders = $result['orders'];
            if ($orders->count() > 1) {
                return redirect()->route('sms.account.numbers')->with('ok', "成功购买了 {$orders->count()} 个号码，可以在列表查看详情。");
            }
            
            $order = $orders->first();
            return redirect()->route('sms.order.show', ['token' => $order->token]);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['order' => $e->getMessage()]);
        }
    }

    public function showOrder($token)
    {
        $order = SmsOrder::with(['service', 'country', 'latestPayment'])->where('token', $token)->firstOrFail();
        return view('sms.order', compact('order'));
    }

    public function orderStatus(Request $request, $token)
    {
        $order = SmsOrder::with(['service', 'country', 'latestPayment'])->where('token', $token)->firstOrFail();
        $isInventory = strpos((string) $order->provider_activation_id, 'inventory:') === 0;
        if ($order->status === SmsOrder::STATUS_WAITING_CODE || ($isInventory && $order->status === SmsOrder::STATUS_COMPLETED && $request->boolean('force'))) {
            $order = $this->orders->pollCode($order, $request->boolean('force'));
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
            return back()->withErrors(['cancel' => __('sms.order.cancel_not_allowed')]);
        }
        try {
            $this->orders->cancelOrder($order);
            return back()->with('ok', __('sms.status.cancelled'));
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

        if (empty($data['order_sn']) && empty($data['email']) && $request->user()) {
            $data['email'] = $request->user()->email;
        }
        if (! empty($data['email']) && empty($data['order_sn']) && ! $request->user()) {
            return back()->withInput()->withErrors(['query' => '邮箱批量查询需要先登录对应账号；未登录时请使用订单号查询。']);
        }
        if (! empty($data['email']) && $request->user() && strcasecmp($data['email'], $request->user()->email) !== 0) {
            return back()->withInput()->withErrors(['query' => '只能查询当前登录邮箱下的订单。']);
        }

        $orders = $this->orders->findForQuery($data['order_sn'] ?? null, $data['email'] ?? null, $data['query_password'] ?? null, $request->user());
        return view('sms.query', compact('orders'));
    }

    public function statusText($status)
    {
        return __("sms.status.$status") === "sms.status.$status" ? $status : __("sms.status.$status");
    }
}
