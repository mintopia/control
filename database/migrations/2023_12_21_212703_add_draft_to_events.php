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
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('draft')->default(true)->after('code');
            $table->timestamp('seating_opens_at')->nullable()->default(null)->after('seating_locked');
            $table->timestamp('seating_closes_at')->nullable()->default(null)->after('seating_opens_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumns(['draft', 'seating_opens_at', 'seating_closes_at']);
        });
    }
};
