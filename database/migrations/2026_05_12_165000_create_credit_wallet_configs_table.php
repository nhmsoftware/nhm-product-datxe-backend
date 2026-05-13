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
        Schema::create('credit_wallet_configs', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_balance', 15, 2)->default(0);
            $table->boolean('auto_lock')->default(true);
            $table->text('commission_rule')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // Insert default config
        DB::table('credit_wallet_configs')->insert([
            'min_balance' => 50000,
            'auto_lock' => true,
            'commission_rule' => 'Default commission deduction rule',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_wallet_configs');
    }
};
