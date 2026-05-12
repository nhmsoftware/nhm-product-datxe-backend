<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            
            // Polymorphic for Ride or Order (Food/Delivery)
            $table->unsignedBigInteger('refundable_id');
            $table->string('refundable_type');
            $table->index(['refundable_id', 'refundable_type']);

            $table->decimal('amount', 15, 2);
            $table->text('reason');
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, REJECTED, COMPLETED
            
            $table->text('admin_note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            
            $table->json('evidence')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
