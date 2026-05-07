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
        Schema::create('penalty_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->smallInteger('violation_type');
            $table->smallInteger('applicable_role');
            $table->integer('violation_threshold');
            $table->smallInteger('penalty_type');
            $table->integer('penalty_duration')->nullable()->comment('Duration in minutes for temporary ban');
            $table->decimal('monetary_amount', 15, 2)->nullable();
            $table->integer('reputation_points')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['violation_type', 'applicable_role', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalty_rules');
    }
};
