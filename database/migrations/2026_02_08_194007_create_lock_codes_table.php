<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lock_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lock_id')->constrained()->onDelete('cascade');
            $table->string('tuya_password_id')->nullable()->index(); // Can be null if creation fails but we save it? No, usually we save after success.
            $table->string('name');
            $table->string('pin'); // Storing plain text as requested for admin visibility
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lock_codes');
    }
};
