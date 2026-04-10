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
        Schema::table('customer_saved_addresses', function (Blueprint $table) {
            $table->unique(
                ['customer_id', 'lat', 'lng'],
                'unique_customer_location'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_saved_addresses', function (Blueprint $table) {
            $table->dropUnique('unique_customer_location');
        });
    }
};
