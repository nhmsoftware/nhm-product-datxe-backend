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
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->decimal('current_lat', 10, 7)->nullable()->after('full_name');
            $table->decimal('current_lng', 10, 7)->nullable()->after('current_lat');
            
            $table->index(['current_lat', 'current_lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropIndex(['current_lat', 'current_lng']);
            $table->dropColumn(['current_lat', 'current_lng']);
        });
    }
};
