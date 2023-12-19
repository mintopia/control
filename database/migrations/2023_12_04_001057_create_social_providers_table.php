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
        Schema::create('social_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('provider_class');
            $table->boolean('supports_auth')->default(false);
            $table->boolean('enabled')->default(false);
            $table->boolean('auth_enabled')->default(false);
            $table->longText('client_id')->nullable()->default(null);
            $table->longText('client_secret')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_providers');
    }
};
