<?php

return [
    'provider' => env('SMS_PROVIDER', 'inventory'),

    'locale' => [
        'default' => env('SMS_DEFAULT_LOCALE', 'zh_CN'),
        'supported' => [
            'zh_CN' => '中文',
            'en' => 'English',
        ],
    ],

    'herosms' => [
        'api_key' => env('HEROSMS_API_KEY'),
        'base_url' => env('HEROSMS_BASE_URL', 'https://hero-sms.com/stubs/handler_api.php'),
        'timeout' => (int) env('HEROSMS_TIMEOUT', 15),
        'lang' => env('HEROSMS_SERVICE_LANG', 'cn'),
        'price_currency' => env('HEROSMS_PRICE_CURRENCY', 'USD'),
    ],

    'us62' => [
        'api_key' => env('US62_API_KEY'),
        'base_url' => env('US62_BASE_URL', 'https://api.62-us.com'),
        'timeout' => (int) env('US62_TIMEOUT', 15),
        'default_country' => (int) env('US62_DEFAULT_COUNTRY', 1),
        'default_endday' => (int) env('US62_DEFAULT_ENDDAY', 60),
    ],

    'pricing' => [
        'exchange_rate' => (float) env('SMS_USD_CNY_RATE', 7.3),
        'markup_multiplier' => (float) env('SMS_DEFAULT_MARKUP_MULTIPLIER', 1.35),
        'fixed_fee' => (float) env('SMS_DEFAULT_FIXED_FEE', 0),
        'min_profit' => (float) env('SMS_DEFAULT_MIN_PROFIT', 0.5),
        'min_price' => (float) env('SMS_DEFAULT_MIN_PRICE', 3),
        'reprice_tolerance' => (float) env('SMS_REPRICE_TOLERANCE', 0.00),
        'cost_tolerance_usd' => (float) env('SMS_PROVIDER_COST_TOLERANCE_USD', 0.00),
    ],

    'order' => [
        'expire_minutes' => (int) env('SMS_ORDER_EXPIRE_MINUTES', 15),
        'poll_interval_seconds' => (int) env('SMS_POLL_INTERVAL_SECONDS', 8),
        'poll_timeout_minutes' => (int) env('SMS_POLL_TIMEOUT_MINUTES', 20),
    ],

    'recharge' => [
        'expire_minutes' => (int) env('SMS_RECHARGE_EXPIRE_MINUTES', 15),
        'default_plans' => [
            ['name' => '体验包', 'amount' => 20, 'bonus_amount' => 0, 'badge' => '适合测试', 'sort_order' => 10],
            ['name' => '常用包', 'amount' => 50, 'bonus_amount' => 2, 'badge' => '推荐', 'sort_order' => 20],
            ['name' => '进阶包', 'amount' => 100, 'bonus_amount' => 6, 'badge' => '更划算', 'sort_order' => 30],
            ['name' => '批量包', 'amount' => 200, 'bonus_amount' => 16, 'badge' => '批量接码', 'sort_order' => 40],
            ['name' => '商务包', 'amount' => 500, 'bonus_amount' => 55, 'badge' => '高频用户', 'sort_order' => 50],
            ['name' => '旗舰包', 'amount' => 1000, 'bonus_amount' => 130, 'badge' => 'API/批发', 'sort_order' => 60],
        ],
    ],

    'admin' => [
        'username' => env('SMS_ADMIN_USER', 'admin'),
        'password' => env('SMS_ADMIN_PASSWORD'),
    ],

    'payments' => [
        'alipay' => [
            'name' => env('SMS_YIPAY_ALIPAY_NAME', '支付宝'),
            'driver' => 'yipay',
            'pay_check' => env('SMS_YIPAY_ALIPAY_PAY_CHECK', 'alipay'),
            'enabled' => filter_var(env('SMS_YIPAY_ALIPAY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'merchant_id' => env('SMS_YIPAY_PID'),
            'merchant_key' => env('SMS_YIPAY_GATEWAY'),
            'merchant_secret' => env('SMS_YIPAY_KEY'),
            'method' => 'jump',
            'sort_order' => 10,
        ],
        'wxpay' => [
            'name' => env('SMS_YIPAY_WXPAY_NAME', '微信'),
            'driver' => 'yipay',
            'pay_check' => env('SMS_YIPAY_WXPAY_PAY_CHECK', 'wxpay'),
            'enabled' => filter_var(env('SMS_YIPAY_WXPAY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'merchant_id' => env('SMS_YIPAY_PID'),
            'merchant_key' => env('SMS_YIPAY_GATEWAY'),
            'merchant_secret' => env('SMS_YIPAY_KEY'),
            'method' => 'jump',
            'sort_order' => 20,
        ],
        'usdt_bep20' => [
            'name' => env('SMS_EPUSDT_BEP20_NAME', 'USDT (BEP-20)'),
            'driver' => 'epusdt',
            'pay_check' => env('SMS_EPUSDT_BEP20_PAY_CHECK', 'usdt.bep20'),
            'enabled' => filter_var(env('SMS_EPUSDT_BEP20_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'merchant_id' => env('SMS_EPUSDT_API_KEY'),
            'merchant_key' => env('SMS_EPUSDT_BEP20_PLACEHOLDER', ''),
            'merchant_secret' => env('SMS_EPUSDT_API_KEY'),
            'endpoint_url' => env('SMS_EPUSDT_BASE_URL'),
            'method' => 'jump',
            'sort_order' => 30,
        ],
        'usdt_trc20' => [
            'name' => env('SMS_EPUSDT_TRC20_NAME', 'USDT (TRC-20)'),
            'driver' => 'epusdt',
            'pay_check' => env('SMS_EPUSDT_TRC20_PAY_CHECK', 'usdt.trc20'),
            'enabled' => filter_var(env('SMS_EPUSDT_TRC20_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'merchant_id' => env('SMS_EPUSDT_API_KEY'),
            'merchant_key' => env('SMS_EPUSDT_TRC20_PLACEHOLDER', ''),
            'merchant_secret' => env('SMS_EPUSDT_API_KEY'),
            'endpoint_url' => env('SMS_EPUSDT_BASE_URL'),
            'method' => 'jump',
            'sort_order' => 40,
        ],
    ],
];
