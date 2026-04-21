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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // Using BigInt for consistency with project
            $table->unsignedBigInteger('ride_id')->index();
            $table->unsignedBigInteger('sender_id')->index();
            $table->unsignedBigInteger('receiver_id')->index();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Indexes for performance
            $table->index(['ride_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
