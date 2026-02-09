<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->onDelete('cascade');
            $table->string('number'); // Apartment number (e.g., "101", "2A")
            $table->string('floor')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['building_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
