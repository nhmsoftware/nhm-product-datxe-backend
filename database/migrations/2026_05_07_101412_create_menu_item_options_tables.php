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
        Schema::create('merchant_menu_item_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('merchant_menu_items')->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('merchant_menu_item_toppings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('merchant_menu_items')->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->integer('max_quantity')->default(1);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_menu_item_toppings');
        Schema::dropIfExists('merchant_menu_item_sizes');
    }
};
