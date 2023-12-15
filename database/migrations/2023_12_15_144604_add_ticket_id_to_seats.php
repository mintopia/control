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
        Schema::table('seats', function (Blueprint $table) {
            $table->foreignId('ticket_id')->after('seating_plan_id')->nullable()->default(null)->constrained()->nullOnDelete();
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['seat_id']);
            $table->dropColumn('seat_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropColumn('ticket_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('seat_id')->after('ticket_type_id')->nullable()->default(null)->constrained()->cascadeOnDelete();
        });
    }
};
