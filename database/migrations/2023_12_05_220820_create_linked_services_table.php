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
        Schema::create('linked_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('service');
            $table->string('external_id')->nullable()->default(null);
            $table->string('name')->nullable()->default(null);
            $table->string('avatar_url')->nullable()->default(null);
            $table->string('access_token')->nullable()->default(null);
            $table->string('refresh_token')->nullable()->default(null);
            $table->timestamp('refresh_token_expires_at')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('linked_services');
    }
};
