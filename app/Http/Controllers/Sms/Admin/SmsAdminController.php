<?php

namespace App\Http\Controllers\Sms\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsCountry;
use App\Models\Sms\SmsHomeSlide;
use App\Models\Sms\SmsOrder;
use App\Models\Sms\SmsPrice;
use App\Models\Sms\SmsProviderLog;
use App\Models\Sms\SmsRechargeOrder;
use App\Models\Sms\SmsRechargePlan;
use App\Models\Sms\SmsService;
use App\Models\Sms\SmsWalletLog;
use App\Services\Sms\SmsPriceService;
use App\Services\Sms\SmsSettingService;
use Illuminate\Http\Request;

class SmsAdminController extends Controller
{
    public function dashboard()
    {
        return view('sms.admin.dashboard', [
            'counts' => [
                'services' => SmsService::count(),
                'countries' => SmsCountry::count(),
                'available_prices' => SmsPrice::where('is_available', true)->count(),
                'orders' => SmsOrder::count(),
                'recharges' => SmsRechargeOrder::count(),
                'waiting_code' => SmsOrder::where('status', SmsOrder::STATUS_WAITING_CODE)->count(),
                'refund_required' => SmsOrder::where('status', SmsOrder::STATUS_REFUND_REQUIRED)->count(),
                'wallet_refunded' => SmsOrder::whereNotNull('wallet_refunded_at')->count(),
            ],
        ]);
    }

    public function settings(SmsSettingService $settings)
    {
        return view('sms.admin.settings', [
            'values' => $this->settingValues($settings),
            'paymentMethods' => $this->paymentSettingValues($settings),
        ]);
    }

    public function saveSettings(Request $request, SmsSettingService $settings)
    {
        $plain = [
            'herosms_base_url' => ['type' => 'string', 'group' => 'herosms'],
            'pricing_exchange_rate' => ['type' => 'float', 'group' => 'pricing'],
            'pricing_markup_multiplier' => ['type' => 'float', 'group' => 'pricing'],
            'pricing_fixed_fee' => ['type' => 'float', 'group' => 'pricing'],
            'pricing_min_profit' => ['type' => 'float', 'group' => 'pricing'],
            'pricing_min_price' => ['type' => 'float', 'group' => 'pricing'],
        ];
        foreach ($plain as $key => $meta) {
            if ($request->has($key)) {
                $settings->set($key, $request->input($key), $meta['type'], false, $meta['group']);
            }
        }

        $secret = ['herosms_api_key'];
        foreach ($secret as $key) {
            if ($request->filled($key)) {
                $settings->set($key, $request->input($key), 'string', true, 'herosms');
            }
        }

        foreach (array_keys(config('sms.payments', [])) as $code) {
            $settings->set('payment_' . $code . '_enabled', $request->boolean('payment_' . $code . '_enabled'), 'bool', false, 'payment');
            foreach (['pay_check', 'merchant_id', 'merchant_key', 'endpoint_url'] as $field) {
                $key = 'payment_' . $code . '_' . $field;
                if ($request->has($key)) {
                    $settings->set($key, $request->input($key), 'string', false, 'payment');
                }
            }
            $secretKey = 'payment_' . $code . '_merchant_secret';
            if ($request->filled($secretKey)) {
                $settings->set($secretKey, $request->input($secretKey), 'string', true, 'payment');
            }
        }

        return back()->with('ok', '配置已保存');
    }

    public function services(Request $request)
    {
        $query = SmsService::query()->withCount('prices');
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')->orWhere('provider_code', 'like', '%' . $request->q . '%');
            });
        }
        return view('sms.admin.services', ['services' => $query->orderBy('name')->paginate(50)]);
    }

    public function saveService(Request $request, SmsService $service)
    {
        $data = $request->validate([
            'is_enabled' => ['nullable'],
            'markup_multiplier' => ['nullable', 'numeric'],
            'fixed_fee' => ['nullable', 'numeric'],
            'min_profit' => ['nullable', 'numeric'],
            'min_price' => ['nullable', 'numeric'],
            'sort_order' => ['nullable', 'integer'],
            'is_featured' => ['nullable'],
        ]);
        $service->fill([
            'is_enabled' => $request->boolean('is_enabled'),
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $data['sort_order'] ?? 0,
            'markup_multiplier' => $data['markup_multiplier'] ?? null,
            'fixed_fee' => $data['fixed_fee'] ?? null,
            'min_profit' => $data['min_profit'] ?? null,
            'min_price' => $data['min_price'] ?? null,
        ])->save();
        return back()->with('ok', '服务已保存');
    }

    public function countries(Request $request)
    {
        $query = SmsCountry::query()->withCount('prices');
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')->orWhere('provider_id', $request->q);
            });
        }
        return view('sms.admin.countries', ['countries' => $query->orderBy('name')->paginate(50)]);
    }

    public function saveCountry(Request $request, SmsCountry $country)
    {
        $data = $request->validate([
            'is_enabled' => ['nullable'],
            'markup_multiplier' => ['nullable', 'numeric'],
            'fixed_fee' => ['nullable', 'numeric'],
            'min_profit' => ['nullable', 'numeric'],
            'min_price' => ['nullable', 'numeric'],
        ]);
        $country->fill([
            'is_enabled' => $request->boolean('is_enabled'),
            'markup_multiplier' => $data['markup_multiplier'] ?? null,
            'fixed_fee' => $data['fixed_fee'] ?? null,
            'min_profit' => $data['min_profit'] ?? null,
            'min_price' => $data['min_price'] ?? null,
        ])->save();
        return back()->with('ok', '国家已保存');
    }

    public function prices(Request $request)
    {
        $query = SmsPrice::with(['service', 'country'])->orderByDesc('synced_at');
        if ($request->filled('service')) {
            $query->where('provider_service_code', $request->service);
        }
        if ($request->filled('country')) {
            $query->where('provider_country_id', $request->country);
        }
        return view('sms.admin.prices', ['prices' => $query->paginate(80)]);
    }

    public function syncPrices(Request $request, SmsPriceService $priceService)
    {
        $result = $priceService->syncAll($request->input('service') ?: null, $request->input('country') ?: null);
        return back()->with('ok', '同步完成：国家 ' . $result['countries'] . '，服务 ' . $result['services'] . '，价格 ' . $result['prices']);
    }

    public function orders(Request $request)
    {
        $query = SmsOrder::with(['service', 'country', 'latestPayment'])->orderByDesc('created_at');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_sn', 'like', '%' . $request->q . '%')
                    ->orWhere('phone_number', 'like', '%' . $request->q . '%')
                    ->orWhere('provider_activation_id', 'like', '%' . $request->q . '%');
            });
        }
        return view('sms.admin.orders', ['orders' => $query->paginate(50)]);
    }

    public function logs(Request $request)
    {
        $query = SmsProviderLog::orderByDesc('created_at');
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('success')) {
            $query->where('is_success', (bool) $request->success);
        }
        return view('sms.admin.logs', ['logs' => $query->paginate(80)]);
    }

    public function rechargePlans()
    {
        if (SmsRechargePlan::count() === 0) {
            foreach (config('sms.recharge.default_plans', []) as $plan) {
                SmsRechargePlan::create($plan + ['is_enabled' => true]);
            }
        }
        $plans = SmsRechargePlan::orderBy('sort_order')->orderBy('amount')->paginate(50);
        return view('sms.admin.recharge_plans', compact('plans'));
    }

    public function createRechargePlan(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'bonus_amount' => ['nullable', 'numeric', 'min:0'],
            'badge' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer'],
            'is_enabled' => ['nullable'],
        ]);

        SmsRechargePlan::create([
            'name' => $data['name'],
            'amount' => $data['amount'],
            'bonus_amount' => $data['bonus_amount'] ?? 0,
            'badge' => $data['badge'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return back()->with('ok', '充值档位已新增');
    }

    public function saveRechargePlan(Request $request, SmsRechargePlan $plan)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'bonus_amount' => ['nullable', 'numeric', 'min:0'],
            'badge' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer'],
            'is_enabled' => ['nullable'],
        ]);

        $plan->fill([
            'name' => $data['name'],
            'amount' => $data['amount'],
            'bonus_amount' => $data['bonus_amount'] ?? 0,
            'badge' => $data['badge'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_enabled' => $request->boolean('is_enabled'),
        ])->save();

        return back()->with('ok', '充值档位已保存');
    }

    public function recharges(Request $request)
    {
        $query = SmsRechargeOrder::with(['user', 'plan'])->orderByDesc('created_at');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('recharge_sn', 'like', '%' . $request->q . '%')
                    ->orWhere('payment_sn', 'like', '%' . $request->q . '%')
                    ->orWhere('trade_no', 'like', '%' . $request->q . '%');
            });
        }
        return view('sms.admin.recharges', ['recharges' => $query->paginate(50)]);
    }

    public function walletLogs(Request $request)
    {
        $query = SmsWalletLog::with(['user', 'order', 'rechargeOrder'])->orderByDesc('created_at');
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('q')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', '%' . $request->q . '%');
            });
        }
        return view('sms.admin.wallet_logs', ['logs' => $query->paginate(80)]);
    }

    public function homeSlides()
    {
        if (SmsHomeSlide::count() === 0) {
            foreach ([
                ['badge' => 'ZXAIHUB SMS', 'title' => '充值余额，自动接收验证码', 'description' => '选择平台和国家，下单前实时确认成本；扣余额后自动取号、自动等待验证码。', 'image_url' => '/images/home/slide-1.jpg', 'card_title' => '接码流程', 'card_value' => '4 步', 'card_description' => '充值 → 选择 → 取号 → 收码', 'sort_order' => 10],
                ['badge' => '余额模式', 'title' => '没收到验证码，自动退回余额', 'description' => 'HeroSMS 无库存、取号失败、成本异常或超时未收到验证码，系统自动退回用户余额。', 'image_url' => '/images/home/slide-2.jpg', 'card_title' => '退款状态', 'card_value' => '自动', 'card_description' => '记录完整余额流水', 'sort_order' => 20],
            ] as $slide) {
                SmsHomeSlide::create($slide + ['is_enabled' => true]);
            }
        }
        $slides = SmsHomeSlide::orderBy('sort_order')->orderBy('id')->paginate(30);
        return view('sms.admin.home_slides', compact('slides'));
    }

    public function createHomeSlide(Request $request)
    {
        SmsHomeSlide::create($this->slideData($request));
        return back()->with('ok', '首页轮播已新增');
    }

    public function saveHomeSlide(Request $request, SmsHomeSlide $slide)
    {
        $slide->fill($this->slideData($request))->save();
        return back()->with('ok', '首页轮播已保存');
    }

    private function slideData(Request $request)
    {
        $data = $request->validate([
            'badge' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'card_title' => ['nullable', 'string', 'max:80'],
            'card_value' => ['nullable', 'string', 'max:80'],
            'card_description' => ['nullable', 'string', 'max:160'],
            'sort_order' => ['nullable', 'integer'],
            'is_enabled' => ['nullable'],
        ]);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_enabled'] = $request->boolean('is_enabled');
        return $data;
    }


    private function paymentSettingValues(SmsSettingService $settings)
    {
        $methods = config('sms.payments', []);
        foreach ($methods as $code => &$method) {
            foreach (['enabled', 'pay_check', 'merchant_id', 'merchant_key', 'endpoint_url'] as $field) {
                $key = 'payment_' . $code . '_' . $field;
                if ($settings->has($key)) {
                    $method[$field] = $settings->get($key, $method[$field] ?? null);
                }
            }
        }
        unset($method);
        return $methods;
    }

    private function settingValues(SmsSettingService $settings)
    {
        return [
            'herosms_base_url' => $settings->get('herosms_base_url', config('sms.herosms.base_url')),
            'pricing_exchange_rate' => $settings->get('pricing_exchange_rate', config('sms.pricing.exchange_rate')),
            'pricing_markup_multiplier' => $settings->get('pricing_markup_multiplier', config('sms.pricing.markup_multiplier')),
            'pricing_fixed_fee' => $settings->get('pricing_fixed_fee', config('sms.pricing.fixed_fee')),
            'pricing_min_profit' => $settings->get('pricing_min_profit', config('sms.pricing.min_profit')),
            'pricing_min_price' => $settings->get('pricing_min_price', config('sms.pricing.min_price')),
        ];
    }
}
