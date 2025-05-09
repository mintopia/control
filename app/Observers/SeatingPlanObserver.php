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
        if (!$seatingPlan->isDirty('revision')) {
            $seatingPlan->revision++;
        }
    }

    public function saved(SeatingPlan $seatingPlan): void
    {
        if ($seatingPlan->isDirty('revision')) {
            $seatingPlan->queueUpdate();
        }
    }

    /**
     * Handle the SeatingPlan "created" event.
     */
    public function created(SeatingPlan $seatingPlan): void
    {
        //
    }

    /**
     * Handle the SeatingPlan "updated" event.
     */
    public function updated(SeatingPlan $seatingPlan): void
    {
        //
    }

    /**
     * Handle the SeatingPlan "deleted" event.
     */
    public function deleted(SeatingPlan $seatingPlan): void
    {
        //
    }

    /**
     * Handle the SeatingPlan "restored" event.
     */
    public function restored(SeatingPlan $seatingPlan): void
    {
        //
    }

    /**
     * Handle the SeatingPlan "force deleted" event.
     */
    public function forceDeleted(SeatingPlan $seatingPlan): void
    {
        //
    }
}
