<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTranslationsToSmsHomeSlides extends Migration
{
    public function up()
    {
        Schema::table('sms_home_slides', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('card_description');
        });

        foreach (DB::table('sms_home_slides')->get() as $slide) {
            $index = $this->bundledSlideIndex($slide);
            if ($index === null) {
                continue;
            }

            $translations = $this->defaultTranslations()[$index] ?? null;
            if (! $translations) {
                continue;
            }

            DB::table('sms_home_slides')->where('id', $slide->id)->update([
                'badge' => $translations['zh_CN']['badge'],
                'title' => $translations['zh_CN']['title'],
                'description' => $translations['zh_CN']['description'],
                'card_title' => $translations['zh_CN']['card_title'],
                'card_value' => $translations['zh_CN']['card_value'],
                'card_description' => $translations['zh_CN']['card_description'],
                'image_url' => $slide->image_url ?: '/images/home/slide-' . ($index + 1) . '.webp',
                'translations' => json_encode($translations, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function down()
    {
        Schema::table('sms_home_slides', function (Blueprint $table) {
            $table->dropColumn('translations');
        });
    }

    private function bundledSlideIndex($slide): ?int
    {
        $titles = [
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

        $index = array_search($slide->title, $titles, true);
        if ($index !== false) {
            return $index % 3;
        }

        if ($slide->image_url && preg_match('~/images/home/slide-(\\d+)\\.(?:jpg|jpeg|png|webp)(?:\\?.*)?$~i', $slide->image_url, $matches)) {
            $imageIndex = (int) $matches[1] - 1;
            return $imageIndex >= 0 && $imageIndex < 3 ? $imageIndex : null;
        }

        return null;
    }

    private function defaultTranslations(): array
    {
        return [
            [
                'zh_CN' => ['badge' => '长效接码号', 'title' => '60 天长效接码', 'description' => '主售长期可用号码，站内购买后自动交付；手机号、短信和订单记录都在账号里长期可查。', 'card_title' => '号码有效期', 'card_value' => '60 天', 'card_description' => '适合长期账号验证'],
                'en' => ['badge' => 'Long-term SMS', 'title' => '60-day SMS numbers', 'description' => 'We focus on long-validity numbers. After purchase, the number, messages and order history stay available inside your account.', 'card_title' => 'Validity', 'card_value' => '60 Days', 'card_description' => 'Built for long-term verification'],
            ],
            [
                'zh_CN' => ['badge' => '余额工作台', 'title' => '余额支付，自动交付', 'description' => '充值后用账户余额下单，成功后自动发放号码；库存不足、发货失败或异常会自动退回余额。', 'card_title' => '交付方式', 'card_value' => '自动', 'card_description' => '购买 → 发货 → 收码'],
                'en' => ['badge' => 'Balance Workspace', 'title' => 'Balance payment, auto delivery', 'description' => 'Top up once and pay with account balance. Numbers are delivered automatically, and failed delivery returns balance automatically.', 'card_title' => 'Delivery', 'card_value' => 'Auto', 'card_description' => 'Buy → deliver → receive'],
            ],
            [
                'zh_CN' => ['badge' => '订单追踪', 'title' => '订单可查，记录可追踪', 'description' => '登录账号可查看全部号码，也可以通过下单邮箱或订单号找回记录，客服和交流群入口统一配置。', 'card_title' => '记录保存', 'card_value' => '长期', 'card_description' => '邮箱与订单号均可查询'],
                'en' => ['badge' => 'Order Tracking', 'title' => 'Searchable orders and records', 'description' => 'View all numbers after login, or recover records with order email or order number. Support and community links are configured in admin.', 'card_title' => 'Records', 'card_value' => 'Saved', 'card_description' => 'Recover by email or order ID'],
            ],
        ];
    }
}
