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
        Schema::create('user_otp', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 50)->index();
            $table->string('otp_hash');
            $table->unsignedSmallInteger('type'); // UserOtpType enum
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->timestamp('expired_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->unsignedTinyInteger('send_count')->default(1);
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['phone', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_otp');
    }
};
