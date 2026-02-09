<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->unique(); // Tuya device_id
            $table->string('name');
            $table->string('model')->default('X7'); // Lock model
            $table->boolean('active')->default(true);
            $table->timestamp('last_sync')->nullable();
            $table->json('status_data')->nullable(); // Store battery, online status, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locks');
    }
};
