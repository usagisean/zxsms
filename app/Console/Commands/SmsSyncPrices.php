<?php

namespace App\Console\Commands;

use App\Services\Sms\SmsPriceService;
use Illuminate\Console\Command;

class SmsSyncPrices extends Command
{
    protected $signature = 'sms:sync-prices {--service=} {--country=}';
    protected $description = '同步 HeroSMS 国家、服务和价格';

    public function handle(SmsPriceService $prices)
    {
        $result = $prices->syncAll($this->option('service') ?: null, $this->option('country') ?: null);
        $this->info('同步完成：国家 ' . $result['countries'] . '，服务 ' . $result['services'] . '，价格 ' . $result['prices']);
        return 0;
    }
}
