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
        Schema::table('ride_rejects', function (Blueprint $table) {
            // Chúng ta không thể đổi kiểu cột trực tiếp dễ dàng trong một số DB nếu có index,
            // nên cách an toàn nhất là drop index/unique và đổi kiểu.
            $table->dropUnique(['ride_id', 'driver_id']);
            
            $table->string('ride_id')->change();
            $table->string('driver_id')->change();
            
            $table->unique(['ride_id', 'driver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ride_rejects', function (Blueprint $table) {
            $table->dropUnique(['ride_id', 'driver_id']);
        });

        if (config('database.default') === 'pgsql' || \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE ride_rejects ALTER COLUMN ride_id TYPE bigint USING ride_id::bigint');
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE ride_rejects ALTER COLUMN driver_id TYPE bigint USING driver_id::bigint');
        } else {
            Schema::table('ride_rejects', function (Blueprint $table) {
                $table->unsignedBigInteger('ride_id')->change();
                $table->unsignedBigInteger('driver_id')->change();
            });
        }

        Schema::table('ride_rejects', function (Blueprint $table) {
            $table->unique(['ride_id', 'driver_id']);
        });
    }
};
