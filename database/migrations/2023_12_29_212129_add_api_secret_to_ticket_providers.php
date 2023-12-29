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
        Schema::table('ticket_providers', function (Blueprint $table) {
            $table->longText('apisecret')->nullable()->default(null)->after('apikey');
            $table->longText('endpoint')->nullable()->default(null)->after('apisecret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_providers', function (Blueprint $table) {
            $table->dropColumn(['apisecret', 'endpoint']);
        });
    }
};
