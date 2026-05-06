# ZXAIHUB SMS 独立接码站

本项目是独立 Laravel 接码站，目录：`/Users/zhaozhixiang/telegramdown/herosms-web`。  
不会修改原发卡网 `/Users/zhaozhixiang/telegramdown/zxmall-main`。

## 已实现路由

前台：

- `GET /`：完整首页，含轮播、介绍、FAQ、合作/API 入口。
- `GET /sms`：选择平台/国家，登录用户使用余额下单；游客仍可直付下单。
- `GET /sms/order/{token}`：订单详情、号码/验证码展示、一键复制、自动轮询。
- `GET|POST /sms/query`：游客订单号/邮箱/查询密码查询。
- `GET /login`、`GET /register`：邮箱登录/注册，不依赖邮件发送。
- `GET /my-numbers`：用户中心，余额、充值、流水、接码订单。
- `GET|POST /recharge`：充值档位与充值下单。
- `GET /recharge/{token}`：充值订单支付页。

后台（HTTP Basic Auth）：

- `GET /sms-admin`：概览
- `GET|POST /sms-admin/settings`：HeroSMS、价格、支付配置
- `GET /sms-admin/services`：服务启用/加价规则
- `GET /sms-admin/countries`：国家启用/加价规则
- `GET /sms-admin/prices`：价格列表与同步
- `GET /sms-admin/recharge-plans`：充值档位管理
- `GET /sms-admin/recharges`：充值订单
- `GET /sms-admin/wallet-logs`：余额流水
- `GET /sms-admin/orders`：接码订单
- `GET /sms-admin/logs`：HeroSMS API 日志

支付回调：

- `GET|POST /sms/pay/yipay/notify_url`
- `POST /sms/pay/epusdt/notify_url`

## 当前支付/余额模式

推荐正式模式：

1. 用户邮箱注册/登录。
2. 用户选择充值档位：¥20 / ¥50 / ¥100 / ¥200 / ¥500 / ¥1000 等。
3. 支付成功后充值金额 + 赠送金额进入用户余额。
4. 用户购买号码时先从余额扣款。
5. HeroSMS 无库存、取号失败、成本异常或超时未收到验证码时，自动退回到用户余额。
6. 收到验证码后订单完成，扣款保留。

游客仍可直付下单，但游客没有个人余额，失败时会进入人工处理/退款状态；建议引导用户注册后使用余额模式。

## 部署步骤

```bash
cd /Users/zhaozhixiang/telegramdown/herosms-web
php ../composer.phar install
cp .env.example .env
php artisan key:generate
# 编辑 .env 数据库、APP_URL、SMS_ADMIN_PASSWORD、支付配置等
php artisan migrate
php artisan sms:sync-prices
```

定时任务：

```cron
* * * * * cd /Users/zhaozhixiang/telegramdown/herosms-web && php artisan schedule:run >> /dev/null 2>&1
```

## 关键配置

敏感信息建议放 `.env` 或后台配置页（后台保存时密钥会加密入库）：

```env
HEROSMS_API_KEY=
HEROSMS_BASE_URL=https://hero-sms.com/stubs/handler_api.php
SMS_ADMIN_USER=admin
SMS_ADMIN_PASSWORD=请设置强密码

SMS_YIPAY_PID=
SMS_YIPAY_GATEWAY=
SMS_YIPAY_KEY=
SMS_YIPAY_ALIPAY_PAY_CHECK=alipay
SMS_YIPAY_WXPAY_PAY_CHECK=wxpay

SMS_EPUSDT_API_KEY=
SMS_EPUSDT_BASE_URL=
SMS_EPUSDT_BEP20_PAY_CHECK=usdt.bep20
SMS_EPUSDT_TRC20_PAY_CHECK=usdt.trc20
```

## 防亏与自动退款策略

- 定时同步缓存价格到 `sms_prices`。
- `/sms` 只展示缓存售价。
- 用户提交下单时调用 HeroSMS `getPrices` 实时确认成本。
- 如果实时售价高于页面展示价，订单不会创建，前台要求用户确认新报价。
- 余额下单成功后先扣用户余额，再调用 HeroSMS 购买号码。
- 购买号码时传 `maxPrice`，避免 HeroSMS 实际扣费超过确认成本。
- 如 HeroSMS 无库存/异常/成本超限，余额订单会自动退回余额。
- `sms:poll-orders` 会轮询验证码；超过 `SMS_POLL_TIMEOUT_MINUTES` 仍未收到码，会取消 HeroSMS 激活并自动退回余额。

## artisan 命令

```bash
php artisan sms:sync-prices --service=go --country=0
php artisan sms:poll-orders
```
