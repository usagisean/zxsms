<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsTables extends Migration
{
    public function up()
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->boolean('is_secret')->default(false);
            $table->string('group', 50)->default('general')->index();
            $table->timestamps();
        });

        Schema::create('sms_services', function (Blueprint $table) {
            $table->id();
            $table->string('provider_code', 60)->unique();
            $table->string('name', 120);
            $table->boolean('is_enabled')->default(true)->index();
            $table->decimal('markup_multiplier', 10, 4)->nullable();
            $table->decimal('fixed_fee', 10, 2)->nullable();
            $table->decimal('min_profit', 10, 2)->nullable();
            $table->decimal('min_price', 10, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sms_countries', function (Blueprint $table) {
            $table->id();
            $table->integer('provider_id')->unique();
            $table->string('name', 120);
            $table->string('name_en', 120)->nullable();
            $table->string('name_cn', 120)->nullable();
            $table->boolean('provider_visible')->default(true)->index();
            $table->boolean('is_enabled')->default(true)->index();
            $table->boolean('supports_retry')->default(false);
            $table->decimal('markup_multiplier', 10, 4)->nullable();
            $table->decimal('fixed_fee', 10, 2)->nullable();
            $table->decimal('min_profit', 10, 2)->nullable();
            $table->decimal('min_price', 10, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sms_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('sms_services')->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('sms_countries')->cascadeOnDelete();
            $table->string('provider_service_code', 60)->index();
            $table->integer('provider_country_id')->index();
            $table->string('operator', 80)->default('any');
            $table->decimal('cost_usd', 12, 4);
            $table->unsignedInteger('stock_count')->default(0);
            $table->decimal('sale_price', 12, 2);
            $table->boolean('is_available')->default(true)->index();
            $table->timestamp('synced_at')->nullable()->index();
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->unique(['provider_service_code', 'provider_country_id', 'operator'], 'sms_prices_provider_unique');
        });

        Schema::create('sms_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_sn', 32)->unique();
            $table->string('token', 80)->unique();
            $table->foreignId('service_id')->nullable()->constrained('sms_services')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained('sms_countries')->nullOnDelete();
            $table->string('service_code', 60)->index();
            $table->integer('country_code')->index();
            $table->string('email')->nullable()->index();
            $table->string('query_password_hash')->nullable();
            $table->decimal('cost_usd', 12, 4);
            $table->decimal('exchange_rate', 12, 4);
            $table->decimal('markup_multiplier', 10, 4);
            $table->decimal('fixed_fee', 10, 2)->default(0);
            $table->decimal('min_profit', 10, 2)->default(0);
            $table->decimal('min_price', 10, 2)->default(0);
            $table->decimal('sale_price', 12, 2);
            $table->string('currency', 10)->default('CNY');
            $table->string('provider_activation_id', 80)->nullable()->index();
            $table->string('provider_currency', 20)->nullable();
            $table->decimal('provider_cost', 12, 4)->nullable();
            $table->string('phone_number', 60)->nullable();
            $table->string('sms_code', 80)->nullable();
            $table->text('sms_text')->nullable();
            $table->string('status', 40)->default('wait_pay')->index();
            $table->string('status_note', 255)->nullable();
            $table->string('buy_ip', 64)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('code_received_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_polled_at')->nullable();
            $table->json('quote_snapshot')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_order_id')->constrained('sms_orders')->cascadeOnDelete();
            $table->string('provider_activation_id', 80)->nullable()->index();
            $table->string('type', 20)->default('sms');
            $table->string('code', 80)->nullable();
            $table->text('text')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_order_id')->constrained('sms_orders')->cascadeOnDelete();
            $table->string('payment_sn', 40)->unique();
            $table->string('method_code', 60)->index();
            $table->string('driver', 40)->index();
            $table->string('pay_check', 60)->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('CNY');
            $table->string('trade_no', 160)->nullable()->index();
            $table->string('status', 40)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('notify_payload')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sms_provider_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sms_order_id')->nullable()->constrained('sms_orders')->nullOnDelete();
            $table->string('provider', 40)->default('herosms');
            $table->string('action', 80)->index();
            $table->string('method', 10)->default('GET');
            $table->string('url', 500)->nullable();
            $table->json('request_payload')->nullable();
            $table->longText('response_body')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('is_success')->default(false)->index();
            $table->string('error_message', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_provider_logs');
        Schema::dropIfExists('sms_payment_orders');
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('sms_orders');
        Schema::dropIfExists('sms_prices');
        Schema::dropIfExists('sms_countries');
        Schema::dropIfExists('sms_services');
        Schema::dropIfExists('sms_settings');
    }
}
