<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('temp_passwords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lock_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Guest name or identifier
            $table->string('tuya_password_id'); // Tuya's password_id from API
            $table->integer('tuya_sn')->nullable(); // Tuya's sn for log correlation
            $table->string('pin', 7)->nullable(); // Encrypted or masked PIN
            $table->timestamp('effective_time'); // Start time (epoch seconds)
            $table->timestamp('invalid_time'); // End time (epoch seconds)
            $table->enum('status', ['created_cloud', 'syncing', 'active', 'deleted', 'expired'])->default('created_cloud');
            $table->string('external_reference')->nullable(); // CRM reservation ID
            $table->timestamps();

            $table->index('tuya_password_id');
            $table->index('external_reference');
            $table->index(['lock_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_passwords');
    }
};
