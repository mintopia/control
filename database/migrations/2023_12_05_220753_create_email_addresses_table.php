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
        Schema::create('email_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email')->unique();
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_sent_at')->nullable()->default(null);
            $table->timestamp('verified_at')->nullable()->default(null);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('primary_email_id')->references('id')->on('email_addresses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_user_id']);
        });
        Schema::dropIfExists('email_addresses');
    }
};
