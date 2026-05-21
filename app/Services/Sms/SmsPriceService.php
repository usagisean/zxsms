<?php

namespace App\Services\Sms;

use App\Models\Sms\SmsCountry;
use App\Models\Sms\SmsInventoryCard;
use App\Models\Sms\SmsPrice;
use App\Models\Sms\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmsPriceService
{
    /** @var HeroSmsClient */
    private $heroSms;

    public function __construct(HeroSmsClient $heroSms)
    {
        $this->heroSms = $heroSms;
    }

    public function calculateSalePrice($costUsd, SmsService $service = null, SmsCountry $country = null)
    {
        $exchangeRate = $this->settingFloat('exchange_rate', 'sms.pricing.exchange_rate');
        $markup = $this->resolveRule('markup_multiplier', $service, $country, $this->settingFloat('markup_multiplier', 'sms.pricing.markup_multiplier'));
        $fixedFee = $this->resolveRule('fixed_fee', $service, $country, $this->settingFloat('fixed_fee', 'sms.pricing.fixed_fee'));
        $minProfit = $this->resolveRule('min_profit', $service, $country, $this->settingFloat('min_profit', 'sms.pricing.min_profit'));
        $minPrice = $this->resolveRule('min_price', $service, $country, $this->settingFloat('min_price', 'sms.pricing.min_price'));

        $costCny = (float) $costUsd * $exchangeRate;
        $byMarkup = $costCny * (float) $markup + (float) $fixedFee;
        $byProfit = $costCny + (float) $minProfit;
        $sale = max($byMarkup, $byProfit, (float) $minPrice);

        return [
            'cost_usd' => round((float) $costUsd, 4),
            'cost_cny' => round($costCny, 4),
            'exchange_rate' => round($exchangeRate, 4),
            'markup_multiplier' => round((float) $markup, 4),
            'fixed_fee' => round((float) $fixedFee, 2),
            'min_profit' => round((float) $minProfit, 2),
            'min_price' => round((float) $minPrice, 2),
            'sale_price' => round(ceil($sale * 100) / 100, 2),
        ];
    }

    public function syncCountries()
    {
        $payload = $this->heroSms->getCountries();
        $items = $this->normalizeList($payload);
        $count = 0;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $providerId = isset($item['id']) ? (int) $item['id'] : null;
            if ($providerId === null) {
                continue;
            }
            $nameCn = $item['chn'] ?? null;
            $nameEn = $item['eng'] ?? null;
            $name = $nameCn ?: ($nameEn ?: ($item['rus'] ?? (string) $providerId));
            SmsCountry::updateOrCreate(
                ['provider_id' => $providerId],
                [
                    'name' => $name,
                    'name_en' => $nameEn,
                    'name_cn' => $nameCn,
                    'provider_visible' => (int) ($item['visible'] ?? 1) === 1,
                    'supports_retry' => (int) ($item['retry'] ?? 0) === 1,
                    'raw' => $item,
                ]
            );
            $count++;
        }

        return $count;
    }

    public function syncServices($country = null)
    {
        $payload = $this->heroSms->getServices($country, config('sms.herosms.lang', 'cn'));
        $items = $this->normalizeList($payload);
        $count = 0;

        foreach ($items as $key => $item) {
            if (is_string($item)) {
                $code = is_string($key) ? $key : null;
                $name = $item;
            } elseif (is_array($item)) {
                $code = $item['code'] ?? (is_string($key) ? $key : null);
                $name = $item['name'] ?? $code;
            } else {
                continue;
            }
            if (! $code || $this->isMetadataKey($code)) {
                continue;
            }
            SmsService::updateOrCreate(
                ['provider_code' => (string) $code],
                [
                    'name' => (string) ($name ?: $code),
                    'raw' => is_array($item) ? $item : ['code' => $code, 'name' => $name],
                ]
            );
            $count++;
        }

        return $count;
    }

    public function syncPrices($serviceCode = null, $countryId = null)
    {
        $payload = $this->heroSms->getPrices($serviceCode, $countryId);
        return $this->persistPrices($payload, $serviceCode, $countryId);
    }

    public function syncAll($serviceCode = null, $countryId = null)
    {
        if ($this->activeProvider() === 'inventory') {
            return $this->syncInventoryCatalog($serviceCode, $countryId);
        }

        $countries = $this->syncCountries();
        $services = $this->syncServices($countryId);
        $prices = 0;

        if ($serviceCode) {
            $prices += $this->syncPrices($serviceCode, $countryId);
        } else {
            SmsService::query()
                ->whereNotIn('provider_code', $this->metadataKeys())
                ->orderBy('provider_code')
                ->chunk(50, function ($items) use (&$prices, $countryId) {
                    foreach ($items as $service) {
                        try {
                            $prices += $this->syncPrices($service->provider_code, $countryId);
                        } catch (\Throwable $e) {
                            // 单个服务失败时继续同步其他服务。失败详情会在 provider_logs 中保留。
                        }
                    }
                });
        }

        return compact('countries', 'services', 'prices');
    }

    public function getCachedQuote($serviceCode, $countryProviderId)
    {
        $price = SmsPrice::with(['service', 'country'])
            ->where('provider_service_code', $serviceCode)
            ->where('provider_country_id', (int) $countryProviderId)
            ->when($this->activeProvider() === 'inventory', function ($query) {
                $query->where('operator', 'inventory');
            })
            ->when($this->activeProvider() !== 'inventory', function ($query) {
                $query->where('operator', '<>', 'inventory');
            })
            ->where('is_available', true)
            ->orderBy('sale_price')
            ->first();

        if (! $price || ! $price->service || ! $price->country) {
            return null;
        }

        if (! $price->service->is_enabled || ! $price->country->is_enabled || ! $price->country->provider_visible) {
            return null;
        }

        return $price;
    }

    public function getLiveQuote($serviceCode, $countryProviderId)
    {
        if ($this->activeProvider() === 'inventory') {
            return $this->getInventoryQuote($serviceCode, $countryProviderId);
        }

        $service = SmsService::where('provider_code', $serviceCode)->first();
        $country = SmsCountry::where('provider_id', (int) $countryProviderId)->first();
        if (! $service || ! $country) {
            throw new RuntimeException('服务或国家不存在，请先同步价格');
        }
        if (! $service->is_enabled || ! $country->is_enabled || ! $country->provider_visible) {
            throw new RuntimeException('该服务/国家暂不可下单');
        }

        $payload = $this->heroSms->getPrices($serviceCode, (int) $countryProviderId);
        $entry = $this->extractBestPriceEntry($payload, (int) $countryProviderId, $serviceCode);
        if (! $entry || (int) $entry['count'] <= 0 || (float) $entry['cost'] <= 0) {
            $this->markUnavailable($serviceCode, (int) $countryProviderId);
            throw new RuntimeException('HeroSMS 当前无库存或价格异常');
        }

        $pricing = $this->calculateSalePrice((float) $entry['cost'], $service, $country);
        $price = SmsPrice::updateOrCreate(
            [
                'provider_service_code' => $serviceCode,
                'provider_country_id' => (int) $countryProviderId,
                'operator' => $entry['operator'],
            ],
            [
                'service_id' => $service->id,
                'country_id' => $country->id,
                'cost_usd' => $pricing['cost_usd'],
                'stock_count' => (int) $entry['count'],
                'sale_price' => $pricing['sale_price'],
                'is_available' => true,
                'synced_at' => now(),
                'raw' => $entry['raw'],
            ]
        );

        return [$price, $pricing, $payload];
    }

    public function publicCatalog()
    {
        $query = SmsPrice::with(['service', 'country'])
            ->where('is_available', true)
            ->where('stock_count', '>', 0);

        if ($this->activeProvider() === 'inventory') {
            $query->where('operator', 'inventory');
        } else {
            $query->where('operator', '<>', 'inventory');
        }

        $prices = $query->orderBy('sale_price')
            ->get()
            ->filter(function (SmsPrice $price) {
                return $price->service && $price->country
                    && $price->service->is_enabled
                    && $price->country->is_enabled
                    && $price->country->provider_visible
                    && ((float) $price->cost_usd > 0 || $price->operator === 'inventory');
            });

        $services = [];
        $countriesByService = [];
        foreach ($prices as $price) {
            $serviceCode = $price->provider_service_code;
            $countryCode = (string) $price->provider_country_id;
            $services[$serviceCode] = [
                'code' => $serviceCode,
                'name' => $price->service->name,
                'is_featured' => (bool) $price->service->is_featured,
                'sort_order' => (int) $price->service->sort_order,
            ];
            if (! isset($countriesByService[$serviceCode])) {
                $countriesByService[$serviceCode] = [];
            }
            if (! isset($countriesByService[$serviceCode][$countryCode])) {
                $countriesByService[$serviceCode][$countryCode] = [
                    'id' => $price->country->provider_id,
                    'name' => $price->country->name,
                    'price' => (float) $price->sale_price,
                    'stock' => (int) $price->stock_count,
                    'synced_at' => optional($price->synced_at)->toDateTimeString(),
                    'title' => $price->title,
                    'description' => $price->description,
                    'sold_count' => (int) $price->base_sold_count,
                    'max_quantity' => (int) ($price->max_quantity ?: 10),
                ];
            }
        }

        usort($services, function ($a, $b) {
            if ((int) $a['is_featured'] !== (int) $b['is_featured']) {
                return (int) $b['is_featured'] <=> (int) $a['is_featured'];
            }
            if ((int) $a['sort_order'] !== (int) $b['sort_order']) {
                return (int) $a['sort_order'] <=> (int) $b['sort_order'];
            }
            return strcmp($a['name'], $b['name']);
        });

        return [
            'services' => array_values($services),
            'countriesByService' => $countriesByService,
        ];
    }


    public function syncInventoryCatalog($serviceCode = null, $countryId = null)
    {
        $minValidUntil = $this->inventoryMinValidityDate();
        $query = SmsInventoryCard::query()->where('status', SmsInventoryCard::STATUS_AVAILABLE);
        $query->where(function ($q) use ($minValidUntil) {
            $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $minValidUntil);
        });
        if ($serviceCode) {
            $query->where('service_code', (string) $serviceCode);
        }
        if ($countryId !== null && $countryId !== '') {
            $query->where('country_code', (int) $countryId);
        }

        $cards = $query->get();
        $groups = [];
        foreach ($cards as $card) {
            $key = $card->service_code . '|' . (int) $card->country_code;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'service_code' => $card->service_code,
                    'service_name' => $card->service_name ?: $card->service_code,
                    'country_code' => (int) $card->country_code,
                    'country_name' => $card->country_name ?: (string) $card->country_code,
                    'count' => 0,
                    'min_sale' => null,
                    'min_cost_cny' => null,
                ];
            }
            $groups[$key]['count']++;
            $sale = (float) $card->sale_price;
            $cost = (float) $card->cost_cny;
            if ($groups[$key]['min_sale'] === null || $sale < $groups[$key]['min_sale']) {
                $groups[$key]['min_sale'] = $sale;
            }
            if ($groups[$key]['min_cost_cny'] === null || $cost < $groups[$key]['min_cost_cny']) {
                $groups[$key]['min_cost_cny'] = $cost;
            }
        }

        $now = Carbon::now();
        $countryCount = 0;
        $serviceCount = 0;
        $priceCount = 0;
        $seenServices = [];
        $seenCountries = [];
        $exchangeRate = max(0.0001, $this->settingFloat('exchange_rate', 'sms.pricing.exchange_rate'));

        DB::beginTransaction();
        try {
            foreach ($groups as $group) {
                $service = SmsService::updateOrCreate(
                    ['provider_code' => (string) $group['service_code']],
                    ['name' => (string) $group['service_name'], 'raw' => ['provider' => 'inventory']]
                );
                if (! isset($seenServices[$service->id])) {
                    $seenServices[$service->id] = true;
                    $serviceCount++;
                }

                $country = SmsCountry::updateOrCreate(
                    ['provider_id' => (int) $group['country_code']],
                    [
                        'name' => (string) $group['country_name'],
                        'name_en' => (string) $group['country_name'],
                        'name_cn' => (string) $group['country_name'],
                        'provider_visible' => true,
                        'raw' => ['provider' => 'inventory'],
                    ]
                );
                if (! isset($seenCountries[$country->id])) {
                    $seenCountries[$country->id] = true;
                    $countryCount++;
                }

                $costCny = (float) ($group['min_cost_cny'] ?: 0);
                $salePrice = (float) ($group['min_sale'] ?: 0);
                $priceKey = [
                    'provider_service_code' => (string) $group['service_code'],
                    'provider_country_id' => (int) $group['country_code'],
                    'operator' => 'inventory',
                ];
                $existingPrice = SmsPrice::where($priceKey)->first();
                $existingRaw = is_array(optional($existingPrice)->raw) ? $existingPrice->raw : [];
                $manualHidden = ! empty($existingRaw['manual_hidden']);
                $raw = array_merge($existingRaw, ['provider' => 'inventory', 'cost_cny' => $costCny]);
                SmsPrice::updateOrCreate(
                    $priceKey,
                    [
                        'service_id' => $service->id,
                        'country_id' => $country->id,
                        'cost_usd' => round($costCny / $exchangeRate, 4),
                        'stock_count' => (int) $group['count'],
                        'sale_price' => $salePrice,
                        'is_available' => ! $manualHidden && $group['count'] > 0 && $salePrice > 0,
                        'synced_at' => $now,
                        'raw' => $raw,
                    ]
                );
                $priceCount++;
            }
            SmsPrice::where('operator', 'inventory')
                ->when($serviceCode, function ($query) use ($serviceCode) {
                    $query->where('provider_service_code', $serviceCode);
                })
                ->when($countryId !== null && $countryId !== '', function ($query) use ($countryId) {
                    $query->where('provider_country_id', (int) $countryId);
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('synced_at')->orWhere('synced_at', '<>', $now);
                })
                ->update(['is_available' => false, 'stock_count' => 0]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['countries' => $countryCount, 'services' => $serviceCount, 'prices' => $priceCount];
    }

    private function getInventoryQuote($serviceCode, $countryProviderId)
    {
        $service = SmsService::where('provider_code', $serviceCode)->first();
        $country = SmsCountry::where('provider_id', (int) $countryProviderId)->first();
        if (! $service || ! $country) {
            $this->syncInventoryCatalog($serviceCode, $countryProviderId);
            $service = SmsService::where('provider_code', $serviceCode)->first();
            $country = SmsCountry::where('provider_id', (int) $countryProviderId)->first();
        }
        if (! $service || ! $country) {
            throw new RuntimeException('该商品不存在或暂无库存');
        }
        if (! $service->is_enabled || ! $country->is_enabled || ! $country->provider_visible) {
            throw new RuntimeException('该商品暂不可下单');
        }
        $priceKey = [
            'provider_service_code' => (string) $serviceCode,
            'provider_country_id' => (int) $countryProviderId,
            'operator' => 'inventory',
        ];
        $existingPrice = SmsPrice::where($priceKey)->first();
        $existingRaw = is_array(optional($existingPrice)->raw) ? $existingPrice->raw : [];
        if (! empty($existingRaw['manual_hidden'])) {
            throw new RuntimeException('该商品已隐藏，暂不可下单');
        }

        $cards = SmsInventoryCard::where('service_code', $serviceCode)
            ->where('country_code', (int) $countryProviderId)
            ->where('status', SmsInventoryCard::STATUS_AVAILABLE)
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $this->inventoryMinValidityDate());
            })
            ->orderBy('sale_price')
            ->get();

        if ($cards->isEmpty()) {
            $this->markUnavailable($serviceCode, (int) $countryProviderId);
            throw new RuntimeException('当前商品暂无库存');
        }

        $card = $cards->first();
        $exchangeRate = max(0.0001, $this->settingFloat('exchange_rate', 'sms.pricing.exchange_rate'));
        $costCny = (float) $card->cost_cny;
        $pricing = [
            'cost_usd' => round($costCny / $exchangeRate, 4),
            'cost_cny' => round($costCny, 2),
            'exchange_rate' => round($exchangeRate, 4),
            'markup_multiplier' => 1,
            'fixed_fee' => 0,
            'min_profit' => round(max(0, (float) $card->sale_price - $costCny), 2),
            'min_price' => round((float) $card->sale_price, 2),
            'sale_price' => round((float) $card->sale_price, 2),
        ];

        $price = SmsPrice::updateOrCreate(
            $priceKey,
            [
                'service_id' => $service->id,
                'country_id' => $country->id,
                'cost_usd' => $pricing['cost_usd'],
                'stock_count' => $cards->count(),
                'sale_price' => $pricing['sale_price'],
                'is_available' => true,
                'synced_at' => now(),
                'raw' => array_merge($existingRaw, ['provider' => 'inventory', 'cost_cny' => $costCny]),
            ]
        );

        return [$price, $pricing, ['provider' => 'inventory', 'stock' => $cards->count(), 'card_id' => $card->id]];
    }

    private function persistPrices($payload, $serviceCode = null, $countryId = null)
    {
        $count = 0;
        $countries = $this->normalizePricePayload($payload, $countryId, $serviceCode);
        $now = Carbon::now();

        DB::beginTransaction();
        try {
            foreach ($countries as $countryKey => $operators) {
                $country = SmsCountry::where('provider_id', (int) $countryKey)->first();
                if (! $country) {
                    continue;
                }
                foreach ($operators as $operator => $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }
                    $serviceCodes = $serviceCode ? [$serviceCode => $entry] : $this->extractServiceEntries($entry);
                    foreach ($serviceCodes as $svcCode => $svcEntry) {
                        $service = SmsService::firstOrCreate(
                            ['provider_code' => (string) $svcCode],
                            ['name' => (string) $svcCode, 'raw' => ['code' => (string) $svcCode]]
                        );
                        $cost = isset($svcEntry['cost']) ? (float) $svcEntry['cost'] : null;
                        $stock = isset($svcEntry['count']) ? (int) $svcEntry['count'] : 0;
                        $available = $cost !== null && $cost > 0 && $stock > 0;
                        $pricing = $available
                            ? $this->calculateSalePrice($cost, $service, $country)
                            : ['sale_price' => 0, 'cost_usd' => (float) $cost];

                        SmsPrice::updateOrCreate(
                            [
                                'provider_service_code' => (string) $svcCode,
                                'provider_country_id' => (int) $countryKey,
                                'operator' => (string) $operator,
                            ],
                            [
                                'service_id' => $service->id,
                                'country_id' => $country->id,
                                'cost_usd' => $pricing['cost_usd'],
                                'stock_count' => $stock,
                                'sale_price' => $pricing['sale_price'],
                                'is_available' => $available,
                                'synced_at' => $now,
                                'raw' => $svcEntry,
                            ]
                        );
                        $count++;
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $count;
    }

    private function extractBestPriceEntry($payload, $countryProviderId, $serviceCode = null)
    {
        $countries = $this->normalizePricePayload($payload, $countryProviderId, $serviceCode);
        $operators = $countries[(string) $countryProviderId] ?? $countries[$countryProviderId] ?? [];
        $best = null;
        foreach ($operators as $operator => $entry) {
            if (! is_array($entry) || ! isset($entry['cost'])) {
                continue;
            }
            $candidate = [
                'operator' => (string) $operator,
                'cost' => (float) $entry['cost'],
                'count' => (int) ($entry['count'] ?? 0),
                'raw' => $entry,
            ];
            if ($candidate['count'] <= 0 || $candidate['cost'] <= 0) {
                continue;
            }
            if (! $best || $candidate['cost'] < $best['cost']) {
                $best = $candidate;
            }
        }
        return $best;
    }

    private function markUnavailable($serviceCode, $countryProviderId)
    {
        SmsPrice::where('provider_service_code', $serviceCode)
            ->where('provider_country_id', (int) $countryProviderId)
            ->update(['is_available' => false, 'stock_count' => 0]);
    }

    private function normalizeList($payload)
    {
        $payload = $this->unwrapPayload($payload, ['data', 'items', 'list', 'services', 'countries', 'values', 'result']);
        if (! is_array($payload)) {
            return [];
        }
        return $payload;
    }

    private function normalizePricePayload($payload, $countryId = null, $serviceCode = null)
    {
        $payload = $this->unwrapPayload($payload, ['data', 'prices', 'values', 'result', 'items', 'list', 'countries', 'services']);
        if (! is_array($payload)) {
            return [];
        }

        $normalized = [];
        $entries = [];
        $this->collectPriceEntries($payload, $entries, [
            'country' => $countryId !== null ? (string) $countryId : null,
            'service' => $serviceCode !== null ? (string) $serviceCode : null,
            'operator' => null,
        ], $countryId !== null ? (string) $countryId : null, $serviceCode !== null ? (string) $serviceCode : null);

        foreach ($entries as $entry) {
            if ($countryId !== null && (string) $entry['country'] !== (string) $countryId) {
                continue;
            }
            if ($serviceCode !== null && (string) $entry['service'] !== (string) $serviceCode) {
                continue;
            }

            $countryKey = (string) $entry['country'];
            $operator = (string) (($entry['operator'] !== null && $entry['operator'] !== '') ? $entry['operator'] : 'any');
            $resolvedService = ($entry['service'] !== null && $entry['service'] !== '') ? $entry['service'] : ($serviceCode ?: 'unknown');
            $svcCode = (string) $resolvedService;

            if (! isset($normalized[$countryKey])) {
                $normalized[$countryKey] = [];
            }

            if ($serviceCode !== null) {
                $normalized[$countryKey][$operator] = $entry['payload'];
            } else {
                if (! isset($normalized[$countryKey][$operator])) {
                    $normalized[$countryKey][$operator] = [];
                }
                $normalized[$countryKey][$operator][$svcCode] = $entry['payload'];
            }
        }

        return $normalized;
    }

    private function collectPriceEntries($node, array &$entries, array $context, $forcedCountryId = null, $forcedServiceCode = null)
    {
        if (! is_array($node)) {
            return;
        }

        $priceEntry = $this->normalizeCostEntry($node);
        if ($priceEntry !== null) {
            $country = ($context['country'] !== null && $context['country'] !== '') ? $context['country'] : $forcedCountryId;
            $service = ($context['service'] !== null && $context['service'] !== '') ? $context['service'] : $forcedServiceCode;
            if ($country !== null && $service !== null) {
                $entries[] = [
                    'country' => (string) $country,
                    'service' => (string) $service,
                    'operator' => ($context['operator'] !== null && $context['operator'] !== '') ? $context['operator'] : 'any',
                    'payload' => $priceEntry,
                ];
            }
            return;
        }

        foreach ($node as $key => $child) {
            if (! is_array($child)) {
                continue;
            }

            $next = $context;
            $keyString = (string) $key;

            if ($this->isMetadataKey($keyString)) {
                $this->collectPriceEntries($child, $entries, $next, $forcedCountryId, $forcedServiceCode);
                continue;
            }

            $looksLikeCountry = $this->looksLikeCountryKey($keyString)
                && ($forcedCountryId === null || $keyString === (string) $forcedCountryId || $next['country'] === null);

            if ($looksLikeCountry) {
                $next['country'] = $keyString;
            } elseif ($forcedServiceCode !== null && $keyString === (string) $forcedServiceCode) {
                $next['service'] = $keyString;
            } elseif ($forcedServiceCode === null && $next['country'] !== null && $next['service'] === null) {
                // 无 service 参数时，SMS-Activate 常见格式为 country => service => {cost,count}
                $next['service'] = $keyString;
            } elseif ($next['country'] !== null && $next['operator'] === null) {
                // 有 service 参数时，country => operator => {cost,count}
                $next['operator'] = $keyString;
            }

            $this->collectPriceEntries($child, $entries, $next, $forcedCountryId, $forcedServiceCode);
        }
    }

    private function normalizeCostEntry(array $entry)
    {
        $cost = $entry['cost']
            ?? $entry['price']
            ?? $entry['service_cost']
            ?? $entry['costUsd']
            ?? $entry['cost_usd']
            ?? null;

        if ($cost === null || ! is_numeric($cost)) {
            return null;
        }

        $count = $entry['count']
            ?? $entry['stock']
            ?? $entry['quantity']
            ?? $entry['qty']
            ?? $entry['available']
            ?? $entry['total']
            ?? 0;

        $entry['cost'] = (float) $cost;
        $entry['count'] = is_numeric($count) ? (int) $count : 0;

        return $entry;
    }

    private function extractServiceEntries(array $entry)
    {
        if ($this->normalizeCostEntry($entry) !== null) {
            return ['unknown' => $entry];
        }
        return $entry;
    }

    private function unwrapPayload($payload, array $candidateKeys)
    {
        while (is_array($payload)) {
            $unwrapped = false;
            foreach ($candidateKeys as $key) {
                if (isset($payload[$key]) && is_array($payload[$key]) && $this->looksLikeEnvelope($payload, $key)) {
                    $payload = $payload[$key];
                    $unwrapped = true;
                    break;
                }
            }
            if (! $unwrapped) {
                break;
            }
        }
        return $payload;
    }

    private function looksLikeEnvelope(array $payload, $dataKey)
    {
        $meta = array_merge($this->metadataKeys(), [(string) $dataKey]);
        foreach (array_keys($payload) as $key) {
            if (! in_array((string) $key, $meta, true)) {
                return false;
            }
        }
        return true;
    }

    private function looksLikeCountryKey($key)
    {
        return is_numeric($key) && (string) (int) $key === (string) $key;
    }

    private function isMetadataKey($key)
    {
        return in_array(strtolower((string) $key), $this->metadataKeys(), true);
    }

    private function metadataKeys()
    {
        return ['status', 'success', 'message', 'msg', 'error', 'errors', 'code', 'data', 'result', 'values', 'items', 'list', 'services', 'countries', 'prices'];
    }

    private function resolveRule($field, SmsService $service = null, SmsCountry $country = null, $default = null)
    {
        if ($service && $service->{$field} !== null) {
            return (float) $service->{$field};
        }
        if ($country && $country->{$field} !== null) {
            return (float) $country->{$field};
        }
        return (float) $default;
    }

    private function settingFloat($shortKey, $configKey)
    {
        return (float) app(SmsSettingService::class)->get('pricing_' . $shortKey, config($configKey));
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
}
