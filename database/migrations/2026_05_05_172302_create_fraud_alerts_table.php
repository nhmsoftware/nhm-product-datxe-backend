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
        Schema::create('fraud_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('target_type'); // CUSTOMER, DRIVER, MERCHANT, TRANSACTION
            $table->string('target_id');   // ID của đối tượng
            $table->string('fraud_type');  // FAKE_GPS, PROMO_ABUSE, GHOST_RIDE, etc.
            $table->string('risk_level');  // LOW, MEDIUM, HIGH, CRITICAL
            $table->string('status')->default('PENDING'); // PENDING, INVESTIGATING, RESOLVED, DISMISSED
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('evidence_metadata')->nullable(); // Lưu trữ bằng chứng chi tiết
            $table->timestamp('detected_at')->useCurrent();
            $table->string('handled_by')->nullable(); // Admin ID xử lý
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('status');
            $table->index('risk_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fraud_alerts');
    }
};
