<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsHomeSlide;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('sms.home', [
            'slides' => $this->slides(),
        ]);
    }

    private function slides()
    {
        $slides = SmsHomeSlide::where('is_enabled', true)->orderBy('sort_order')->orderBy('id')->get();
        if ($slides->isEmpty()) {
            return collect($this->defaultSlides());
        }

        $translated = $slides->map(function ($slide) {
            $slide = $this->applyLocalizedSlideCopy($slide);
            if ($this->hasSlideTranslations($slide)) {
                return $slide;
            }

            return $this->translateBundledSlide($slide);
        });

        if ($this->isBundledSlideSet($slides) && $translated->count() < count($this->defaultSlides())) {
            $used = $translated->map(function ($slide) {
                return $this->bundledSlideIndex($slide);
            })->filter(function ($index) {
                return $index !== null;
            })->values()->all();

            foreach ($this->defaultSlides() as $index => $slide) {
                if (! in_array($index, $used, true)) {
                    $translated->push($slide);
                }
            }
        }

        return $translated;
    }

    private function defaultSlides()
    {
        $items = __('sms.home.slides');
        if (! is_array($items)) {
            $items = [];
        }

        return collect($items)->values()->map(function ($slide, $index) {
            return (object) ($slide + [
                'image_url' => asset('images/home/slide-' . ($index + 1) . '.webp'),
                'sort_order' => ($index + 1) * 10,
                'is_enabled' => true,
            ]);
        })->all();
    }

    private function translateBundledSlide($slide)
    {
        $index = $this->bundledSlideIndex($slide);
        if ($index === null) {
            return $slide;
        }

        $translated = $this->defaultSlides()[$index] ?? null;
        if (! $translated) {
            return $slide;
        }

        foreach (['badge', 'title', 'description', 'card_title', 'card_value', 'card_description'] as $field) {
            $slide->{$field} = $translated->{$field} ?? $slide->{$field};
        }

        if (! $slide->image_url || $this->isBundledImage($slide->image_url)) {
            $slide->image_url = $translated->image_url;
        }

        return $slide;
    }

    private function applyLocalizedSlideCopy($slide)
    {
        if (! method_exists($slide, 'localizedCopy')) {
            return $slide;
        }

        foreach ($slide->localizedCopy(app()->getLocale()) as $field => $value) {
            $slide->{$field} = $value;
        }

        return $slide;
    }

    private function hasSlideTranslations($slide): bool
    {
        return ! empty($slide->translations) && is_array($slide->translations);
    }

    private function isBundledSlideSet($slides): bool
    {
        if ($slides->isEmpty()) {
            return false;
        }

        return $slides->every(function ($slide) {
            return $this->bundledSlideIndex($slide) !== null;
        });
    }

    private function bundledSlideIndex($slide): ?int
    {
        $legacyTitles = [
            '充值余额，自动接收验证码',
            '没收到验证码，自动退回余额',
            '防亏本报价，不按旧价成交',
            '余额充值，自动接码',
            '未收到码，余额退回',
            '实时成本，防止亏本',
            '60 天长效接码',
            '余额支付，自动交付',
            '订单可查，记录可追踪',
        ];

        $index = array_search($slide->title, $legacyTitles, true);
        if ($index !== false) {
            return $index % 3;
        }

        if ($slide->image_url && preg_match('~/images/home/slide-(\\d+)\\.(?:jpg|jpeg|png|webp)(?:\\?.*)?$~i', $slide->image_url, $matches)) {
            $imageIndex = (int) $matches[1] - 1;
            return $imageIndex >= 0 && $imageIndex < 3 ? $imageIndex : null;
        }

        return null;
    }

    private function isBundledImage(string $imageUrl): bool
    {
        return (bool) preg_match('~/images/home/slide-\\d+\\.(?:jpg|jpeg|png|webp)(?:\\?.*)?$~i', $imageUrl);
    }
}
