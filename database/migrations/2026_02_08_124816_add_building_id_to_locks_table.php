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
        Schema::table('locks', function (Blueprint $table) {
            $table->foreignId('apartment_id')->nullable()->change();
            $table->foreignId('building_id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locks', function (Blueprint $table) {
            // We cannot easily revert nullability if there are null values, 
            // but for down() we attempt to make it required again.
            // This might fail if data exists.

            // $table->foreignId('apartment_id')->nullable(false)->change(); 
            // Skipping strict revert of apartment_id to avoid data loss issues in dev.

            $table->dropConstrainedForeignId('building_id');
        });
    }
};
