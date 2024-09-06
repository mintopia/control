<?php

use App\Models\SeatingPlan;
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
        Schema::table('seating_plans', function (Blueprint $table) {
            $table->integer('scale')->default(100)->after('order');
            $table->integer('image_height')->after('image_url')->nullable();
            $table->integer('image_width')->after('image_height')->nullable();
        });

        foreach (SeatingPlan::all() as $plan) {
            if ($plan->image_url)
            {
                $plan->image_height = getimagesize($plan->image_url)[1];
                $plan->image_width =  getimagesize($plan->image_url)[0];
                $plan->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seating_plans', function (Blueprint $table) {
            $table->dropColumn('scale');
            $table->dropColumn('image_height');
            $table->dropColumn('image_width');
        });
    }
};
