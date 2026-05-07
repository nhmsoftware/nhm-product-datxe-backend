<?php

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
        Schema::create('merchant_combos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_profile_id')->constrained('merchant_profiles')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->string('image_path')->nullable();
            $table->boolean('is_available')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['merchant_profile_id', 'is_available', 'order']);
        });

        Schema::create('merchant_combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained('merchant_combos')->onDelete('cascade');
            $table->foreignId('menu_item_id')->constrained('merchant_menu_items')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->index(['combo_id', 'menu_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_combo_items');
        Schema::dropIfExists('merchant_combos');
    }
};
