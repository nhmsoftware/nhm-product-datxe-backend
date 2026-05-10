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
        Schema::table('food_orders', function (Blueprint $table) {
            $table->boolean('is_cancel_requested')->default(false)->after('status');
            $table->string('cancel_request_reason')->nullable()->after('is_cancel_requested');
            $table->timestamp('cancel_requested_at')->nullable()->after('cancel_request_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $table) {
            //
        });
    }
};
