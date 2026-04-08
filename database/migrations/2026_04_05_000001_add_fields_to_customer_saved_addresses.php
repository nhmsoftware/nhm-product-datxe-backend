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
            $table->string('receiver_name', 100)->nullable()->after('address_text');
            $table->string('receiver_phone', 20)->nullable()->after('receiver_name');
            $table->string('note', 255)->nullable()->after('receiver_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_saved_addresses', function (Blueprint $table) {
            $table->dropColumn(['receiver_name', 'receiver_phone', 'note']);
        });
    }
};