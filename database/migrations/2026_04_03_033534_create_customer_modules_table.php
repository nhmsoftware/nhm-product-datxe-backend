<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade'); //
            $table->string('full_name', 100); //
            $table->unsignedTinyInteger('gender')->nullable(); //
            $table->date('birthday')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_saved_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customer_profiles')->onDelete('cascade'); //
            $table->unsignedTinyInteger('label'); //
            $table->string('name', 100)->nullable(); //
            $table->text('address_text'); //
            $table->decimal('lat', 10, 7); //
            $table->decimal('lng', 10, 7); //
            $table->boolean('is_default')->default(false); //
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_saved_addresses');
        Schema::dropIfExists('customer_profiles');
    }
};
