<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsHomeSlidesAndFeaturedServices extends Migration
{
    public function up()
    {
        Schema::create('sms_home_slides', function (Blueprint $table) {
            $table->id();
            $table->string('badge', 80)->nullable();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('card_title', 80)->nullable();
            $table->string('card_value', 80)->nullable();
            $table->string('card_description', 160)->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('sms_services', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_enabled')->index();
        });
    }

    public function down()
    {
        Schema::table('sms_services', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
        Schema::dropIfExists('sms_home_slides');
    }
}
