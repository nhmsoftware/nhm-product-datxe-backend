<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->unsignedTinyInteger('tracking_status')->nullable()->after('status'); // TrackingStatus trạng thái chuyến đi (1: Chờ giao, 2: Đang giao, 3: Đã giao)
            $table->timestamp('driver_assigned_at')->nullable()->after('tracking_status'); // DriverAssignedAt thời gian giao chuyến đi
            $table->timestamp('driver_arrived_at')->nullable()->after('driver_assigned_at'); // DriverArrivedAt thời gian driver đến
            $table->timestamp('tracking_last_ping_at')->nullable()->after('driver_arrived_at'); // TrackingLastPingAt thời gian cuối cùng ping của driver

            $table->index(['tracking_status', 'tracking_last_ping_at']);
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropIndex(['tracking_status', 'tracking_last_ping_at']);
            $table->dropColumn([
                'tracking_status',
                'driver_assigned_at',
                'driver_arrived_at',
                'tracking_last_ping_at',
            ]);
        });
    }
};
