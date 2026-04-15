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
        Schema::create('reward_wallets', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->unsignedBigInteger('customer_id')->unique(); // FK to users.id
            $blueprint->unsignedInteger('balance')->default(0);
            $blueprint->unsignedInteger('total_earned')->default(0);
            $blueprint->unsignedInteger('total_used')->default(0);
            
            $blueprint->timestamps();
            $blueprint->softDeletes();
        });

        Schema::create('reward_transactions', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->unsignedBigInteger('customer_id'); // FK to users.id
            $blueprint->unsignedTinyInteger('type'); // RewardTransactionType: 1: Earn, 2: Redeem, 3: Expire
            $blueprint->integer('points'); 
            $blueprint->string('description', 255);
            $blueprint->nullableMorphs('reference'); // reference_type, reference_id

            $blueprint->timestamps();
            $blueprint->softDeletes();

            $blueprint->index(['customer_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_transactions');
        Schema::dropIfExists('reward_wallets');
    }
};
