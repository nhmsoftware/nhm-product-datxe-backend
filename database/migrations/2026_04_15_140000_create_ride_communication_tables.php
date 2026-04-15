<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ride_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained('rides')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('sender_type');
            $table->text('message');
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();

            $table->index(['ride_id', 'created_at']);
            $table->index(['sender_id', 'sender_type']);
        });

        Schema::create('ride_call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained('rides')->onDelete('cascade');
            $table->foreignId('caller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('callee_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('caller_type');
            $table->unsignedTinyInteger('status')->default(1);
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['ride_id', 'status']);
            $table->index(['caller_id', 'callee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_call_logs');
        Schema::dropIfExists('ride_chat_messages');
    }
};
