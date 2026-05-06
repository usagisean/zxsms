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
        if (SmsHomeSlide::count() === 0) {
            foreach ($this->defaultSlides() as $slide) {
                SmsHomeSlide::create($slide + ['is_enabled' => true]);
            }
        }

        $slides = SmsHomeSlide::where('is_enabled', true)->orderBy('sort_order')->orderBy('id')->get();
        return $slides->isEmpty() ? collect($this->defaultSlides()) : $slides;
    }

    private function defaultSlides()
    {
        return [
            [
                'badge' => 'ZXAIHUB SMS',
                'title' => '充值余额，自动接收验证码',
                'description' => '选择平台和国家，下单前实时确认成本；扣余额后自动取号、自动等待验证码。',
                'image_url' => asset('images/home/slide-1.jpg'),
                'card_title' => '接码流程',
                'card_value' => '4 步',
                'card_description' => '充值 → 选择 → 取号 → 收码',
                'sort_order' => 10,
            ],
            [
                'badge' => '余额模式',
                'title' => '没收到验证码，自动退回余额',
                'description' => 'HeroSMS 无库存、取号失败、成本异常或超时未收到验证码，系统自动退回用户余额。',
                'image_url' => asset('images/home/slide-2.jpg'),
                'card_title' => '退款状态',
                'card_value' => '自动',
                'card_description' => '记录完整余额流水',
                'sort_order' => 20,
            ],
            [
                'badge' => '实时价格',
                'title' => '防亏本报价，不按旧价成交',
                'description' => '前台展示缓存价，提交下单前重新请求 HeroSMS 最新成本，价格上涨会要求重新确认。',
                'image_url' => asset('images/home/slide-3.jpg'),
                'card_title' => '成本确认',
                'card_value' => '实时',
                'card_description' => '价格异常自动拦截',
                'sort_order' => 30,
            ],
        ];
    }
}
