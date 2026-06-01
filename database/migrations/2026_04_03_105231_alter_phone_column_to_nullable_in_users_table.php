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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update any null phones to a unique string to prevent NOT NULL and UNIQUE violations during rollback
        if (config('database.default') === 'pgsql' || \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement("UPDATE users SET phone = 'dummy_' || id WHERE phone IS NULL");
        } else {
            \Illuminate\Support\Facades\DB::statement("UPDATE users SET phone = CONCAT('dummy_', id) WHERE phone IS NULL");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
        });
    }
};
