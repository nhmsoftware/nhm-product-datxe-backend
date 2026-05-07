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
            $table->unsignedTinyInteger('scheduled_dispatch_mode')->default(1)->after('is_free_mode');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_global_settings', function (Blueprint $table) {
            $table->dropColumn('scheduled_dispatch_mode');
        });
    }
};
