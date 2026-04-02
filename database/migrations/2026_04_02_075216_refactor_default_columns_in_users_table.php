<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // name: không dùng trong hệ thống (tên lưu ở customer_profiles/driver_profiles)
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        // email: optional, unique chỉ khi có giá trị (partial unique index)
        DB::statement('DROP INDEX IF EXISTS users_email_unique');
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE email IS NOT NULL');

        // is_phone_verified: đổi từ timestamp sang boolean
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_phone_verified');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_phone_verified')->default(false)->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        DB::statement('DROP INDEX IF EXISTS users_email_unique');
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_phone_verified');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('is_phone_verified')->nullable()->after('is_verified');
        });
    }
};
