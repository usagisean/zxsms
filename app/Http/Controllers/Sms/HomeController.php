<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsHomeSlide;
use App\Services\Sms\SmsPaymentService;
use App\Services\Sms\SmsPriceService;

class HomeController extends Controller
{
    public function __invoke(SmsPriceService $prices, SmsPaymentService $payments)
    {
        $catalog = $prices->publicCatalog();
        $methods = $payments->enabledMethods();

        return view('sms.home', [
            'catalog' => $catalog,
            'methods' => $methods,
            'popularServices' => array_slice($catalog['services'] ?? [], 0, 8),
            'slides' => $this->slides(),
        ]);
    }

    private function slides()
    {
        $slides = SmsHomeSlide::where('is_enabled', true)->orderBy('sort_order')->orderBy('id')->get();
        if ($slides->isEmpty()) {
            return collect($this->defaultSlides());
        }

        return $slides->map(function ($slide) {
            return $this->translateBundledSlide($slide);
        });
    }

    private function defaultSlides()
    {
        $items = __('sms.home.slides');
        if (! is_array($items)) {
            $items = [];
        }

        return collect($items)->values()->map(function ($slide, $index) {
            return (object) ($slide + [
                'image_url' => asset('images/home/slide-' . ($index + 1) . '.jpg'),
                'sort_order' => ($index + 1) * 10,
                'is_enabled' => true,
            ]);
        })->all();
    }

    private function translateBundledSlide($slide)
    {
        $legacyTitles = [
            '充值余额，自动接收验证码',
            '没收到验证码，自动退回余额',
            '防亏本报价，不按旧价成交',
        ];

        $index = array_search($slide->title, $legacyTitles, true);
        if ($index === false) {
            return $slide;
        }

        $translated = $this->defaultSlides()[$index] ?? null;
        if (! $translated) {
            return $slide;
        }

        foreach (['badge', 'title', 'description', 'card_title', 'card_value', 'card_description'] as $field) {
            $slide->{$field} = $translated->{$field} ?? $slide->{$field};
        }

        return $slide;
    }
}
