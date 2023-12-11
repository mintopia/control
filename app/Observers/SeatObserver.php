<?php

namespace App\Observers;

use App\Models\Seat;

class SeatObserver
{
    public function saving(Seat $seat): void
    {
        if (!$seat->label) {
            $seat->label = "{$seat->row}{$seat->number}";
        }
    }

    /**
     * Handle the Seat "created" event.
     */
    public function created(Seat $seat): void
    {
        //
    }

    /**
     * Handle the Seat "updated" event.
     */
    public function updated(Seat $seat): void
    {
        //
    }

    /**
     * Handle the Seat "deleted" event.
     */
    public function deleted(Seat $seat): void
    {
        //
    }

    /**
     * Handle the Seat "restored" event.
     */
    public function restored(Seat $seat): void
    {
        //
    }

    /**
     * Handle the Seat "force deleted" event.
     */
    public function forceDeleted(Seat $seat): void
    {
        //
    }
}
