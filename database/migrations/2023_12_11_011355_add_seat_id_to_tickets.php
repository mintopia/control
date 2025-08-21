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
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('seat_id')->after('ticket_type_id')->nullable()->default(null)->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // SQLite does not support dropping columns reliably via ALTER TABLE.
            // Skip dropping the foreign key/column when using SQLite to avoid errors
            // during test runs which commonly use an in-memory SQLite connection.
            if (Schema::getConnection()->getDriverName() === 'sqlite') {
                return;
            }

            $table->dropForeign(['seat_id']);
            $table->dropColumn('seat_id');
        });
    }
};
