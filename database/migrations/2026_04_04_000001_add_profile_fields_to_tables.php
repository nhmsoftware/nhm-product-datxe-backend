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
        // Thêm trường vào bảng users cho profile
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address', 500)->nullable()->after('avatar');
            }
            if (!Schema::hasColumn('users', 'citizen_id')) {
                $table->string('citizen_id', 20)->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Thêm trường vào bảng customer_profiles
        Schema::table('customer_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_profiles', 'citizen_id')) {
                $table->string('citizen_id', 20)->nullable()->after('birthday');
            }
            if (!Schema::hasColumn('customer_profiles', 'address')) {
                $table->string('address', 500)->nullable()->after('citizen_id');
            }
            if (!Schema::hasColumn('customer_profiles', 'avatar')) {
                $table->string('avatar')->nullable()->after('address');
            }
            if (!Schema::hasColumn('customer_profiles', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Thêm trường vào bảng driver_profiles
        Schema::table('driver_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('driver_profiles', 'license_number')) {
                $table->string('license_number', 50)->nullable()->after('vehicle_number');
            }
            if (!Schema::hasColumn('driver_profiles', 'license_front_image')) {
                $table->string('license_front_image')->nullable()->after('license_number');
            }
            if (!Schema::hasColumn('driver_profiles', 'license_back_image')) {
                $table->string('license_back_image')->nullable()->after('license_front_image');
            }
            if (!Schema::hasColumn('driver_profiles', 'average_rating')) {
                $table->decimal('average_rating', 3, 2)->default(0)->after('license_back_image');
            }
            if (!Schema::hasColumn('driver_profiles', 'total_trips')) {
                $table->unsignedInteger('total_trips')->default(0)->after('average_rating');
            }
            if (!Schema::hasColumn('driver_profiles', 'bank_name')) {
                $table->string('bank_name', 100)->nullable()->after('total_trips');
            }
            if (!Schema::hasColumn('driver_profiles', 'bank_account_number')) {
                $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('driver_profiles', 'bank_account_holder')) {
                $table->string('bank_account_holder', 100)->nullable()->after('bank_account_number');
            }
            if (!Schema::hasColumn('driver_profiles', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Tạo bảng merchant_profiles
        Schema::create('merchant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('store_name', 255)->nullable();
            $table->string('store_address', 500)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->boolean('is_open')->default(true);
            $table->string('business_license', 50)->nullable();
            $table->string('business_license_image')->nullable();
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_profiles');

        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'license_number',
                'license_front_image',
                'license_back_image',
                'average_rating',
                'total_trips',
                'bank_name',
                'bank_account_number',
                'bank_account_holder',
            ]);
        });

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['citizen_id', 'address', 'avatar']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['avatar', 'address', 'citizen_id']);
        });
    }
};
