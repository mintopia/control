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
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->default(null)->after('nickname');
            $table->longText('avatar')->nullable()->default(null)->after('name');
            $table->timestamp('terms_agreed_at')->nullable()->default(null)->after('avatar');
            $table->boolean('first_login')->default(true)->after('terms_agreed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumns(['name', 'avatar', 'terms_agreed_at']);
        });
    }
};
