<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWalletFieldsToSmsOrders extends Migration
{
    public function up()
    {
        Schema::table('sms_orders', function (Blueprint $table) {
            $table->decimal('wallet_amount', 12, 2)->default(0)->after('sale_price');
            $table->timestamp('wallet_paid_at')->nullable()->after('paid_at');
            $table->timestamp('wallet_refunded_at')->nullable()->after('wallet_paid_at');
            $table->string('wallet_refund_reason', 255)->nullable()->after('wallet_refunded_at');
        });
    }

    public function down()
    {
        Schema::table('sms_orders', function (Blueprint $table) {
            $table->dropColumn(['wallet_amount', 'wallet_paid_at', 'wallet_refunded_at', 'wallet_refund_reason']);
        });
    }
}
