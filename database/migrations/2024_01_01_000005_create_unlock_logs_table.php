<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('unlock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lock_id')->constrained()->onDelete('cascade');
            $table->foreignId('temp_password_id')->nullable()->constrained()->onDelete('set null');
            $table->string('unlock_method'); // unlock_temporary, unlock_fingerprint, unlock_app, etc.
            $table->string('unlock_value')->nullable(); // Tuya sn or other identifier
            $table->string('nick_name')->nullable(); // Name from Tuya log
            $table->timestamp('unlocked_at'); // Actual unlock time from Tuya (ms converted to timestamp)
            $table->json('raw_data')->nullable(); // Full Tuya response for debugging
            $table->timestamps();

            $table->index(['lock_id', 'unlocked_at']);
            $table->index('temp_password_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unlock_logs');
    }
};
