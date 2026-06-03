<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * UC-45 Business Rule #5: Giao dịch Pending cần có thời gian hết hạn.
 * UC-45 Alternative Flow A9: Driver không thanh toán → status = Expired.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('top_ups', function (Blueprint $table) {
            $table->timestamp('expired_at')->nullable()->after('external_id');
            $table->index('expired_at');
        });
    }

    public function down(): void
    {
        Schema::table('top_ups', function (Blueprint $table) {
            $table->dropIndex(['expired_at']);
            $table->dropColumn('expired_at');
        });
    }
};
