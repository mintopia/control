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

    public function saved(Seat $seat): void
    {
        if ($seat->ticket_id) {
            $others = Seat::whereTicketId($seat->ticket_id)->where('id', '<>', $seat->id)->get();
            foreach ($others as $other) {
                $other->ticket()->disassociate();
                $other->saveQuietly();
                if ($other->plan->id !== $seat->plan->id) {
                    $other->plan->updateRevision();
                }
            }
            if ($others && !$seat->isDirty()) {
                $seat->plan->updateRevision();
            }
        }
        if ($seat->isDirty()) {
            $seat->plan->updateRevision();
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
        $seat->plan->updateRevision();
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
