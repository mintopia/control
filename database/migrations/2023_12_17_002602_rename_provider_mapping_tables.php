<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('ticket_type_ticket_providers', 'ticket_type_mappings');
        Schema::rename('event_ticket_providers', 'event_mappings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('ticket_type_mappings', 'ticket_type_ticket_providers');
        Schema::rename('event_mappings', 'event_ticket_providers');
    }
};
