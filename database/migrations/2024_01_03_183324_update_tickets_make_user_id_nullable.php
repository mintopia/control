<?php

use App\Models\User;
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
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign('tickets_user_id_foreign');
            $table->unsignedBigInteger('user_id')->nullable(true)->default(null)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('original_email')->nullable()->default(null)->after('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign('tickets_user_id_foreign');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropColumn('original_email');
        });
    }
};
