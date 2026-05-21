<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductFieldsToSmsPrices extends Migration
{
    public function up()
    {
        Schema::table('sms_prices', function (Blueprint $table) {
            $table->string('title', 120)->nullable()->after('operator')->comment('自定义商品标题');
            $table->text('description')->nullable()->after('title')->comment('自定义商品简介');
            $table->integer('base_sold_count')->default(0)->after('description')->comment('基础虚拟销量');
            $table->integer('max_quantity')->default(50)->after('base_sold_count')->comment('单次最大购买数');
        });
    }

    public function down()
    {
        Schema::table('sms_prices', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'base_sold_count', 'max_quantity']);
        });
    }
}
