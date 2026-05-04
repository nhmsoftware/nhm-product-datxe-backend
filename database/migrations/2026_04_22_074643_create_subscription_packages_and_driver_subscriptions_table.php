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
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->unsignedInteger('duration_days');
            $table->decimal('service_fee_reduction_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('driver_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('package_id');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at');
            $table->string('status')->default('active'); // active, expired, cancelled
            $table->decimal('price_paid', 15, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index('driver_id');
            $table->index(['driver_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_subscriptions');
        Schema::dropIfExists('subscription_packages');
    }
};
