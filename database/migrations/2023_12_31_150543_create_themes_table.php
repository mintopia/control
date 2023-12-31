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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('readonly')->default(false);
            $table->boolean('active')->default(false);
            $table->string('primary');
            $table->string('secondary');
            $table->string('tertiary');
            $table->string('nav_background');
            $table->string('seat_available');
            $table->string('seat_disabled');
            $table->string('seat_taken');
            $table->string('seat_clan');
            $table->string('seat_selected');
            $table->longText('css')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
