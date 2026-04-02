<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Thêm cột
            $table->boolean('is_verified')
                ->default(false)
                ->after('email');

            $table->boolean('is_phone_verified')
                ->default(false)
                ->after('is_verified');

            $table->string('google_id')
                ->nullable()
                ->after('is_phone_verified');

            $table->string('apple_id')
                ->nullable()
                ->after('google_id');

            // Index
            $table->unique('phone');
            $table->index('role');
        });

        // Unique email nullable (PostgreSQL)
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE email IS NOT NULL;');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'is_verified',
                'is_phone_verified',
                'google_id',
                'apple_id'
            ]);

            $table->dropUnique(['phone']);
            $table->dropIndex(['role']);
        });

        DB::statement('DROP INDEX IF EXISTS users_email_unique;');
    }
};
