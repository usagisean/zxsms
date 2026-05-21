<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsInventoryCardsTable extends Migration
{
    public function up()
    {
        Schema::create('sms_inventory_cards', function (Blueprint $table) {
            $table->id();
            $table->string('cdk_code', 80)->unique();
            $table->string('service_code', 60)->index();
            $table->string('service_name', 120);
            $table->integer('country_code')->default(1)->index();
            $table->string('country_name', 120)->default('美国');
            $table->string('phone_number', 60)->index();
            $table->longText('sms_url');
            $table->decimal('cost_cny', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2);
            $table->string('status', 30)->default('available')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sms_order_id')->nullable()->constrained('sms_orders')->nullOnDelete();
            $table->string('sms_code', 80)->nullable();
            $table->text('sms_text')->nullable();
            $table->timestamp('valid_until')->nullable()->index();
            $table->timestamp('sold_at')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_code', 'country_code', 'status'], 'sms_inventory_lookup_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_inventory_cards');
    }
}
