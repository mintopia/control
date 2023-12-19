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
        Schema::create('ticket_type_ticket_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_provider_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_type_ticket_providers');
    }
};
