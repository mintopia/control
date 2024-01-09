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
        Schema::table('social_providers', function (Blueprint $table) {
            $table->boolean('can_be_renamed')->default(false)->after('host');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_providers', function (Blueprint $table) {
            $table->dropColumn('can_be_renamed');
        });
    }
};
