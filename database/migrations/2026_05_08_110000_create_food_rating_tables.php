<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('food_ratings', function (Blueprint $table) {
            $table->char('id', 26)->primary(); // ULID
            $table->char('food_order_id', 26)->unique();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('merchant_id');
            
            $table->unsignedTinyInteger('rating')->comment('1-5 stars');
            $table->text('comment')->nullable();
            
            $table->unsignedTinyInteger('food_quality_rating')->nullable()->comment('1-5 stars');
            $table->unsignedTinyInteger('delivery_time_rating')->nullable()->comment('1-5 stars');
            $table->unsignedTinyInteger('service_rating')->nullable()->comment('1-5 stars');
            
            $table->timestamps();

            $table->foreign('food_order_id')->references('id')->on('food_orders')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchant_profiles')->onDelete('cascade');
        });

        Schema::create('food_item_ratings', function (Blueprint $table) {
            $table->id();
            $table->char('food_rating_id', 26);
            $table->unsignedBigInteger('menu_item_id');
            $table->unsignedTinyInteger('rating')->comment('1-5 stars');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('food_rating_id')->references('id')->on('food_ratings')->onDelete('cascade');
            $table->foreign('menu_item_id')->references('id')->on('merchant_menu_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_item_ratings');
        Schema::dropIfExists('food_ratings');
    }
};
