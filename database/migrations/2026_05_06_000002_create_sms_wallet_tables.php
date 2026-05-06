<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsWalletTables extends Migration
{
    public function up()
    {
        Schema::create('sms_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('total_recharged', 12, 2)->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->decimal('total_refunded', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sms_recharge_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->decimal('amount', 12, 2);
            $table->decimal('bonus_amount', 12, 2)->default(0);
            $table->string('badge', 80)->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sms_recharge_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('sms_recharge_plans')->nullOnDelete();
            $table->string('recharge_sn', 40)->unique();
            $table->string('token', 80)->unique();
            $table->string('payment_sn', 40)->unique();
            $table->string('method_code', 60)->index();
            $table->string('driver', 40)->index();
            $table->string('pay_check', 60)->index();
            $table->decimal('amount', 12, 2);
            $table->decimal('bonus_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 10)->default('CNY');
            $table->string('trade_no', 160)->nullable()->index();
            $table->string('status', 40)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->json('request_payload')->nullable();
            $table->json('notify_payload')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sms_wallet_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sms_order_id')->nullable()->constrained('sms_orders')->nullOnDelete();
            $table->foreignId('recharge_order_id')->nullable()->constrained('sms_recharge_orders')->nullOnDelete();
            $table->string('type', 40)->index();
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('remark', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_wallet_logs');
        Schema::dropIfExists('sms_recharge_orders');
        Schema::dropIfExists('sms_recharge_plans');
        Schema::dropIfExists('sms_wallets');
    }
}
