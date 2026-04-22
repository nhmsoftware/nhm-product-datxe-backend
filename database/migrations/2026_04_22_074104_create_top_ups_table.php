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
        Schema::create('top_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wallet_id');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending'); // pending, success, failed, cancelled
            $table->string('payment_method'); // momo, vnpay, card
            $table->string('external_id')->nullable(); // Reference from Payment Gateway
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('wallet_id');
            $table->index('status');
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('top_ups');
    }
};
