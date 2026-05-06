<?php

namespace App\Console\Commands;

use App\Services\Sms\SmsOrderService;
use App\Services\Sms\SmsRechargeService;
use Illuminate\Console\Command;

class SmsPollOrders extends Command
{
    protected $signature = 'sms:poll-orders {--limit=20}';
    protected $description = '轮询等待验证码的接码订单，并过期未支付订单';

    public function handle(SmsOrderService $orders, SmsRechargeService $recharges)
    {
        $expired = $orders->expireUnpaidOrders();
        $expiredRecharges = $recharges->expirePending();
        $polled = $orders->pollWaitingOrders((int) $this->option('limit'));
        $this->info('已过期未支付订单：' . $expired . '；已过期充值订单：' . $expiredRecharges . '；已轮询订单：' . $polled);
        return 0;
    }
}
