<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alert_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lock_id')->constrained()->onDelete('cascade');
            $table->string('alert_code'); // doorbell, alarm_lock, hijack
            $table->timestamp('alert_time'); // When the alert occurred
            $table->json('raw_data')->nullable(); // Full Tuya alert data
            $table->boolean('notified')->default(false); // Whether CRM was notified
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['lock_id', 'alert_code', 'alert_time']);
            $table->index(['notified', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_logs');
    }
};
