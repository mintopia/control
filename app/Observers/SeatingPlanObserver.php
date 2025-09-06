<?php

namespace App\Observers;

use App\Models\SeatingPlan;

use function App\makePermalink;

class SeatingPlanObserver
{
    public function saving(SeatingPlan $seatingPlan): void
    {
        if (!$seatingPlan->code) {
            $seatingPlan->code = makePermalink($seatingPlan->name);
        }

        if ($seatingPlan->exists && $seatingPlan->isDirty('revision')) {
            $seatingPlan->revision++;
        }
    }

    public function saved(SeatingPlan $seatingPlan): void
    {
        if ($seatingPlan->isDirty('revision')) {
            $seatingPlan->queueUpdate();
        }
    }
}
