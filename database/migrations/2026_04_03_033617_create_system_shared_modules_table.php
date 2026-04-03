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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token');
            $table->string('device_id');
            $table->string('device_type')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'user_id']);
        });

        Schema::create('user_review_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->jsonb('snapshot_data');
            $table->unsignedTinyInteger('kyc_type'); // KycType Enum
            $table->unsignedTinyInteger('kyc_status'); // KycStatus Enum
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('real_name', 255);
            $table->string('path');
            $table->unsignedTinyInteger('disk'); // FileDisk Enum
            $table->unsignedBigInteger('size');
            $table->string('mime_type', 50);
            $table->unsignedTinyInteger('fileable_type'); // FileableType Enum
            $table->unsignedBigInteger('fileable_id');
            $table->timestamps();

            $table->index(['fileable_type', 'fileable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_shared_modules');
    }
};
