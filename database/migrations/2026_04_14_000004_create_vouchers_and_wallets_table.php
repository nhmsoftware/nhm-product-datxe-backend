<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('code')->unique();
            $blueprint->unsignedTinyInteger('service_type'); // 1: Ride, 2: Food, 3: Both
            $blueprint->unsignedTinyInteger('discount_type'); // 1: Fixed, 2: Percent
            $blueprint->decimal('discount_value', 15, 2);
            $blueprint->decimal('min_order_amount', 15, 2)->default(0);
            $blueprint->decimal('max_discount_amount', 15, 2)->nullable();

            $blueprint->timestamp('valid_from');
            $blueprint->timestamp('valid_until');

            $blueprint->unsignedInteger('total_usage_limit')->nullable();
            $blueprint->unsignedInteger('used_count')->default(0);

            $blueprint->boolean('is_active')->default(true);
            $blueprint->text('description')->nullable();

            $blueprint->timestamps();
            $blueprint->softDeletes();

            $blueprint->index(['service_type', 'is_active', 'valid_until']);
        });

        Schema::create('voucher_wallets', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->unsignedBigInteger('customer_id'); // FK to users.id
            $blueprint->unsignedBigInteger('voucher_id'); // FK to vouchers.id
            $blueprint->timestamp('saved_at');
            $blueprint->timestamp('used_at')->nullable();

            $blueprint->timestamps();
            $blueprint->softDeletes();

            $blueprint->unique(['customer_id', 'voucher_id']);
            $blueprint->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_wallets');
        Schema::dropIfExists('vouchers');
    }
};
