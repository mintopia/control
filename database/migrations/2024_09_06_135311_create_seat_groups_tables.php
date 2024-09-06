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
        Schema::create('seat_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('event_id');
            $table->string('class')->nullable();
            $table->timestamps();
        });
        Schema::create('seat_group_assignments', function (Blueprint $table) {
            $table->id();
            $table->integer('seat_group_id');
            $table->string('assignment_type'); //'ticket_type' / 'clan' / 'user'
            $table->integer('assignment_type_id'); // id of above object
            $table->timestamps();
        });
        Schema::table('seats', function (Blueprint $table) {
            $table->integer('seat_group_id')->nullable()->after('seating_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seat_groups');
        Schema::dropIfExists('seat_group_assignments');
        Schema::table('seats', function (Blueprint $table) {
            $table->dropColumn('seat_group_id');
        });
    }
};
