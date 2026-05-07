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
        Schema::table('rides', function (Blueprint $table) {
            // UC-37: Pickup Proof fields
            $table->string('pickup_proof_photo_url')->nullable()->after('driver_arrived_at');
            $table->timestamp('pickup_proof_captured_at')->nullable()->after('pickup_proof_photo_url');
            $table->string('pickup_proof_skip_reason')->nullable()->after('pickup_proof_captured_at');
            $table->text('pickup_proof_note')->nullable()->after('pickup_proof_skip_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_proof_photo_url',
                'pickup_proof_captured_at',
                'pickup_proof_skip_reason',
                'pickup_proof_note',
            ]);
        });
    }
};
