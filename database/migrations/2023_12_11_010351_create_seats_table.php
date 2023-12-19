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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seating_plan_id')->constrained()->cascadeOnDelete();
            $table->integer('x');
            $table->integer('y');
            $table->string('row');
            $table->integer('number');
            $table->string('label');
            $table->string('description')->nullable()->default(null);
            $table->string('class')->nullable()->default(null);
            $table->boolean('disabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
