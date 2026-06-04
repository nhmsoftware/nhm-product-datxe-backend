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
        Schema::table('pricing_global_settings', function (Blueprint $table) {
            $table->boolean('auto_push_internal')->default(false)->after('scheduled_dispatch_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_global_settings', function (Blueprint $table) {
            $table->dropColumn('auto_push_internal');
        });
    }
};
