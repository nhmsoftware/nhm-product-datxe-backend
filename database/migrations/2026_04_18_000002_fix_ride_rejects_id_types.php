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
            
            $table->unsignedBigInteger('ride_id')->change();
            $table->unsignedBigInteger('driver_id')->change();
            
            $table->unique(['ride_id', 'driver_id']);
        });
    }
};
