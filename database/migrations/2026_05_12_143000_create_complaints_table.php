<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            
            // Polymorphic for Ride or Order
            $table->unsignedBigInteger('complaintable_id');
            $table->string('complaintable_type');
            $table->index(['complaintable_id', 'complaintable_type']);

            $table->string('type'); // cancel, fraud, attitude, quality, etc.
            $table->text('content');
            $table->json('evidence')->nullable(); // List of image URLs or file paths
            
            $table->string('status')->default('PENDING'); // PENDING, PROCESSING, RESOLVED, REJECTED, WAITING_FOR_INFO
            $table->string('resolution_action')->nullable(); // REFUND, WARN_DRIVER, WARN_CUSTOMER, REJECT, REQUEST_INFO
            $table->text('admin_note')->nullable();
            
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
