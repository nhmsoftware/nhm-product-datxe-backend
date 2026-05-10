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
        Schema::create('food_orders', function (Blueprint $table) {
            $table->char('id', 26)->primary(); // ULID
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('merchant_id');
            $table->unsignedTinyInteger('status')->default(1)->comment('1: PENDING, 2: CONFIRMED, 3: PREPARING, 4: READY, 5: PICKED_UP, 6: DELIVERED, 7: CANCELLED');
            
            $table->decimal('subtotal_price', 15, 2);
            $table->decimal('delivery_fee', 15, 2);
            $table->decimal('service_fee', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2);
            
            $table->string('delivery_address');
            $table->decimal('delivery_lat', 10, 7);
            $table->decimal('delivery_lng', 11, 7);
            $table->string('customer_phone', 20);
            $table->text('notes')->nullable();
            
            $table->string('voucher_code', 50)->nullable();
            $table->unsignedBigInteger('ride_id')->nullable()->comment('Link to delivery ride');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchant_profiles')->onDelete('cascade');
            $table->index(['customer_id', 'status']);
            $table->index(['merchant_id', 'status']);
        });

        Schema::create('food_order_items', function (Blueprint $table) {
            $table->id();
            $table->char('food_order_id', 26);
            $table->unsignedBigInteger('menu_item_id');
            $table->string('name');
            $table->integer('quantity');
            $table->decimal('price', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('food_order_id')->references('id')->on('food_orders')->onDelete('cascade');
            $table->foreign('menu_item_id')->references('id')->on('merchant_menu_items')->onDelete('cascade');
        });

        Schema::create('food_order_item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_order_item_id')->constrained('food_order_items')->onDelete('cascade');
            $table->string('option_name');
            $table->string('option_value');
            $table->decimal('price', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_order_item_options');
        Schema::dropIfExists('food_order_items');
        Schema::dropIfExists('food_orders');
    }
};
