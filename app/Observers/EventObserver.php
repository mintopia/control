<?php

namespace App\Observers;

use App\Models\Event;

use function App\makePermalink;

class EventObserver
{
    public function saved(Event $event): void
    {
        if ($event->isDirty('seating_locked')) {
            foreach ($event->seatingPlans as $plan) {
                $plan->updateRevision();
            }
        }
    }

    public function saving(Event $event): void
    {
        if (!$event->code) {
            $event->code = makePermalink($event->name);
        }
    }

    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        //
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated(Event $event): void
    {
        //
    }

    /**
     * Handle the Event "deleted" event.
     */
    public function deleted(Event $event): void
    {
        //
    }

    /**
     * Handle the Event "restored" event.
     */
    public function restored(Event $event): void
    {
        //
    }

    /**
     * Handle the Event "force deleted" event.
     */
    public function forceDeleted(Event $event): void
    {
        //
    }
}
