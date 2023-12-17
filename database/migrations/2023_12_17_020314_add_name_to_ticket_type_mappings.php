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
        Schema::table('ticket_type_mappings', function (Blueprint $table) {
            $table->string('name')->nullable()->default(null)->after('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_type_mappings', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
