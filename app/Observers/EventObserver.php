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
}
