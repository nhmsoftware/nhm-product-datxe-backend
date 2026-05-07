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
            // UC-38: Delivery Proof fields
            $table->string('delivery_proof_photo_url')->nullable()->after('pickup_proof_note');
            $table->timestamp('delivery_proof_captured_at')->nullable()->after('delivery_proof_photo_url');
            $table->string('delivery_proof_skip_reason')->nullable()->after('delivery_proof_captured_at');
            $table->text('delivery_proof_note')->nullable()->after('delivery_proof_skip_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_proof_photo_url',
                'delivery_proof_captured_at',
                'delivery_proof_skip_reason',
                'delivery_proof_note',
            ]);
        });
    }
};
