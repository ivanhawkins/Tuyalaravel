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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lock_id')->constrained()->cascadeOnDelete();
            $table->string('guest_name');
            $table->string('pin');
            $table->string('tuya_password_id')->nullable(); // ID from Tuya for updates/deletions
            $table->dateTime('check_in');
            $table->dateTime('check_out');
            $table->string('status')->default('active'); // active, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
