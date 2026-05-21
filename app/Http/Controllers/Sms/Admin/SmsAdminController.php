<?php

namespace App\Http\Controllers\Sms\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Sms\SmsCountry;
use App\Models\Sms\SmsHomeSlide;
use App\Models\Sms\SmsInventoryCard;
use App\Models\Sms\SmsOrder;
use App\Models\Sms\SmsPrice;
use App\Models\Sms\SmsProviderLog;
use App\Models\Sms\SmsRechargeOrder;
use App\Models\Sms\SmsRechargePlan;
use App\Models\Sms\SmsService;
use App\Models\Sms\SmsWalletLog;
use App\Services\Sms\SmsPriceService;
use App\Services\Sms\SmsSettingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'sms_provider' => ['type' => 'string', 'group' => 'provider'],
            'site_name' => ['type' => 'string', 'group' => 'site'],
            'site_domain' => ['type' => 'string', 'group' => 'site'],
            'site_footer_desc' => ['type' => 'string', 'group' => 'site'],
            'support_tg_url' => ['type' => 'string', 'group' => 'contact'],
            'support_tg_label' => ['type' => 'string', 'group' => 'contact'],
            'community_tg_url' => ['type' => 'string', 'group' => 'contact'],
            'community_tg_label' => ['type' => 'string', 'group' => 'contact'],
            'product_validity_days' => ['type' => 'int', 'group' => 'product'],
            'product_min_validity_days' => ['type' => 'int', 'group' => 'product'],
            'product_long_term_note' => ['type' => 'string', 'group' => 'product'],
            'herosms_base_url' => ['type' => 'string', 'group' => 'herosms'],
            'pricing_exchange_rate' => ['type' => 'float', 'group' => 'pricing'],
            'pricing_markup_multiplier' => ['type' => 'float', 'group' => 'pricing'],
            'pricing_fixed_fee' => ['type' => 'float', 'group' => 'pricing'],
            'pricing_min_profit' => ['type' => 'float', 'group' => 'pricing'],
            'pricing_min_price' => ['type' => 'float', 'group' => 'pricing'],
        ];
        foreach ($plain as $key => $meta) {
            if ($request->has($key)) {
                $value = $request->input($key);
                if ($key === 'sms_provider' && ! in_array($value, ['inventory', 'herosms'], true)) {
                    $value = 'inventory';
                }
                if (in_array($key, ['support_tg_url', 'community_tg_url'], true) && $value !== '' && ! preg_match('#^https?://#i', (string) $value)) {
                    $value = 'https://t.me/' . ltrim((string) $value, '@/');
                }
                if (in_array($key, ['product_validity_days', 'product_min_validity_days'], true)) {
                    $value = max(1, (int) $value);
                }
                $settings->set($key, $value, $meta['type'], false, $meta['group']);
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

        if ($settings->get('sms_provider', config('sms.provider', 'inventory')) === 'inventory'
            && ($request->has('product_validity_days') || $request->has('product_min_validity_days') || $request->has('sms_provider'))) {
            try {
                app(SmsPriceService::class)->syncInventoryCatalog();
            } catch (\Throwable $e) {
                // 配置保存不应被库存刷新失败阻断，后台可在“号码库存”里手动刷新。
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
                    ->orWhere('email', 'like', '%' . $request->q . '%')
                    ->orWhere('phone_number', 'like', '%' . $request->q . '%')
                    ->orWhere('provider_activation_id', 'like', '%' . $request->q . '%')
                    ->orWhereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('email', 'like', '%' . $request->q . '%');
                    });
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


    public function users(Request $request)
    {
        $query = User::with('smsWallet')
            ->withCount(['smsOrders', 'smsRechargeOrders'])
            ->orderByDesc('created_at');

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('email', 'like', '%' . $request->q . '%')
                    ->orWhere('name', 'like', '%' . $request->q . '%');
            });
        }

        return view('sms.admin.users', ['users' => $query->paginate(50)]);
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


    public function inventory(Request $request)
    {
        $query = SmsInventoryCard::with(['user', 'order'])->orderByDesc('created_at');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('cdk_code', 'like', '%' . $request->q . '%')
                    ->orWhere('service_name', 'like', '%' . $request->q . '%')
                    ->orWhere('service_code', 'like', '%' . $request->q . '%')
                    ->orWhere('phone_number', 'like', '%' . $request->q . '%');
            });
        }

        $settings = app(SmsSettingService::class);
        $defaultValidityDays = max(1, (int) $settings->get('product_validity_days', 60));
        $minValidityDays = max(1, (int) $settings->get('product_min_validity_days', 30));

        return view('sms.admin.inventory', [
            'cards' => $query->paginate(50),
            'defaultValidityDays' => $defaultValidityDays,
            'minValidityDays' => $minValidityDays,
            'defaultValidUntil' => now()->addDays($defaultValidityDays)->toDateString(),
            'stats' => [
                'available' => SmsInventoryCard::where('status', SmsInventoryCard::STATUS_AVAILABLE)->count(),
                'sold' => SmsInventoryCard::where('status', SmsInventoryCard::STATUS_SOLD)->count(),
                'expired' => SmsInventoryCard::where('status', SmsInventoryCard::STATUS_EXPIRED)->count(),
                'disabled' => SmsInventoryCard::where('status', SmsInventoryCard::STATUS_DISABLED)->count(),
            ],
        ]);
    }

    public function importInventory(Request $request, SmsPriceService $priceService)
    {
        $data = $request->validate([
            'service_code' => ['required', 'string', 'max:60'],
            'service_name' => ['required', 'string', 'max:120'],
            'country_code' => ['required', 'integer'],
            'country_name' => ['required', 'string', 'max:120'],
            'cost_cny' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0.01'],
            'valid_until' => ['nullable', 'date'],
            'lines' => ['required', 'string'],
        ]);

        $created = 0;
        $skipped = 0;
        $settings = app(SmsSettingService::class);
        $defaultValidityDays = max(1, (int) $settings->get('product_validity_days', 60));
        $minValidityDays = max(1, (int) $settings->get('product_min_validity_days', 30));
        $defaultValidUntil = now()->addDays($defaultValidityDays)->toDateString();
        $minValidUntil = Carbon::today()->addDays($minValidityDays);
        $lines = preg_split('/\r\n|\r|\n/', $data['lines']);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 2) {
                $skipped++;
                continue;
            }
            // 推荐格式：+手机号|取码URL。也兼容：平台code|平台名|+手机号|取码URL|售价|有效期
            $serviceCode = (string) $data['service_code'];
            $serviceName = (string) $data['service_name'];
            $countryCode = (int) $data['country_code'];
            $countryName = (string) $data['country_name'];
            $phone = $parts[0] ?? '';
            $url = $parts[1] ?? '';
            $salePrice = (float) $data['sale_price'];
            $costCny = (float) ($data['cost_cny'] ?? 0);
            $validUntil = $data['valid_until'] ?? $defaultValidUntil;

            if (count($parts) >= 4 && filter_var($parts[3], FILTER_VALIDATE_URL)) {
                $serviceCode = $parts[0] ?: $serviceCode;
                $serviceName = $parts[1] ?: $serviceName;
                $phone = $parts[2] ?? '';
                $url = $parts[3] ?? '';
                if (isset($parts[4]) && is_numeric($parts[4])) {
                    $salePrice = (float) $parts[4];
                }
                if (isset($parts[5]) && strtotime($parts[5])) {
                    $validUntil = $parts[5];
                }
            }

            $validUntilDate = Carbon::parse($validUntil)->startOfDay();
            if ($validUntilDate->lt($minValidUntil)) {
                $skipped++;
                continue;
            }
            if ($phone === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                $skipped++;
                continue;
            }
            if (SmsInventoryCard::where('phone_number', $phone)->whereIn('status', [SmsInventoryCard::STATUS_AVAILABLE, SmsInventoryCard::STATUS_SOLD])->exists()) {
                $skipped++;
                continue;
            }

            SmsInventoryCard::create([
                'cdk_code' => $this->makeInventoryCode($serviceCode),
                'service_code' => $serviceCode,
                'service_name' => $serviceName,
                'country_code' => $countryCode,
                'country_name' => $countryName,
                'phone_number' => $phone,
                'sms_url' => $url,
                'cost_cny' => $costCny,
                'sale_price' => $salePrice,
                'status' => SmsInventoryCard::STATUS_AVAILABLE,
                'valid_until' => $validUntilDate->toDateString(),
                'raw' => ['import_line' => $line],
            ]);
            $created++;
        }

        $priceService->syncInventoryCatalog();
        return back()->with('ok', '导入完成：新增 ' . $created . ' 条，跳过 ' . $skipped . ' 条；已刷新前台库存价格。');
    }

    public function syncInventory(SmsPriceService $priceService)
    {
        $result = $priceService->syncInventoryCatalog();
        return back()->with('ok', '库存价格已刷新：国家 ' . $result['countries'] . '，服务 ' . $result['services'] . '，价格 ' . $result['prices']);
    }

    public function homeSlides()
    {
        if (SmsHomeSlide::count() === 0) {
            foreach ([
                ['badge' => 'ZXAIHUB SMS', 'title' => '充值余额，自动接收验证码', 'description' => '选择平台和国家，下单前实时确认成本；扣余额后自动取号、自动等待验证码。', 'image_url' => '/images/home/slide-1.jpg', 'card_title' => '接码流程', 'card_value' => '4 步', 'card_description' => '充值 → 选择 → 取号 → 收码', 'sort_order' => 10],
                ['badge' => '余额模式', 'title' => '没收到验证码，自动退回余额', 'description' => '库存不足、发货失败或取码异常时，系统会自动退回用户余额。', 'image_url' => '/images/home/slide-2.jpg', 'card_title' => '退款状态', 'card_value' => '自动', 'card_description' => '记录完整余额流水', 'sort_order' => 20],
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



    private function makeInventoryCode($serviceCode)
    {
        do {
            $code = 'SMS-' . strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', (string) $serviceCode)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(6));
        } while (SmsInventoryCard::where('cdk_code', $code)->exists());
        return $code;
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
            'sms_provider' => $settings->get('sms_provider', config('sms.provider', 'inventory')),
            'site_name' => $settings->get('site_name', __('sms.brand')),
            'site_domain' => $settings->get('site_domain', __('sms.domain')),
            'site_footer_desc' => $settings->get('site_footer_desc', __('sms.footer.desc')),
            'support_tg_url' => $settings->get('support_tg_url', ''),
            'support_tg_label' => $settings->get('support_tg_label', 'TG 客服'),
            'community_tg_url' => $settings->get('community_tg_url', ''),
            'community_tg_label' => $settings->get('community_tg_label', '万人交流群'),
            'product_validity_days' => $settings->get('product_validity_days', 60),
            'product_min_validity_days' => $settings->get('product_min_validity_days', 30),
            'product_long_term_note' => $settings->get('product_long_term_note', '本站主售 30 天以上长效接码号，常规库存约 60 天有效。'),
            'herosms_base_url' => $settings->get('herosms_base_url', config('sms.herosms.base_url')),
            'pricing_exchange_rate' => $settings->get('pricing_exchange_rate', config('sms.pricing.exchange_rate')),
            'pricing_markup_multiplier' => $settings->get('pricing_markup_multiplier', config('sms.pricing.markup_multiplier')),
            'pricing_fixed_fee' => $settings->get('pricing_fixed_fee', config('sms.pricing.fixed_fee')),
            'pricing_min_profit' => $settings->get('pricing_min_profit', config('sms.pricing.min_profit')),
            'pricing_min_price' => $settings->get('pricing_min_price', config('sms.pricing.min_price')),
        ];
    }
}
