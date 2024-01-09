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
            $table->dropColumn(['client_id', 'client_secret', 'token', 'host']);
        });
        Schema::table('ticket_providers', function (Blueprint $table) {
            $table->dropColumn(['apikey', 'webhook_secret', 'apisecret', 'endpoint']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_providers', function (Blueprint $table) {
            $table->longText('client_id')->nullable()->default(null)->after('auth_enabled');
            $table->longText('client_secret')->nullable()->default(null)->after('client_secret');
            $table->longText('token')->nullable()->default(null)->after('client_secret');
            $table->longText('host')->nullable()->default(null)->after('token');
        });
        Schema::table('ticket_providers', function (Blueprint $table) {
            $table->longText('apikey')->nullable()->default(null)->after('provider_class');
            $table->longText('webhook_secret')->nullable()->default(null)->after('apikey');
            $table->longText('apisecret')->nullable()->default(null)->after('apikey');
            $table->longText('endpoint')->nullable()->default(null)->after('apisecret');
        });
    }
};
