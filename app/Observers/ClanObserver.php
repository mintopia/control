<?php

namespace App\Observers;

use App\Models\Clan;
use App\Models\SeatingPlan;
use function App\makePermalink;

class ClanObserver
{
    public function saved(Clan $clan): void
    {
        if ($clan->isDirty('name')) {
            $plans = SeatingPlan::whereHas('seats.ticket.user.clanMemberships', function ($query) use ($clan) {
                $query->whereClanId($clan->id);
            })->get();
            foreach ($plans as $plan) {
                $plan->updateRevision();
            }
        }
    }

    public function deleting(Clan $clan): void
    {
        $plans = SeatingPlan::whereHas('seats.ticket.user.clanMemberships', function ($query) use ($clan) {
            $query->whereClanId($clan->id);
        })->get();
        foreach ($plans as $plan) {
            $plan->delayedRevisionUpdate();
        }
    }

    /**
     * Handle the Clan "created" event.
     */
    public function created(Clan $clan): void
    {
        //
    }

    public function saving(Clan $clan): void
    {
        if (!$clan->invite_code) {
            $clan->generateCode();
        }
        $clan->code = makePermalink($clan->name);
    }

    /**
     * Handle the Clan "updated" event.
     */
    public function updated(Clan $clan): void
    {
        //
    }

    /**
     * Handle the Clan "deleted" event.
     */
    public function deleted(Clan $clan): void
    {
        //
    }

    /**
     * Handle the Clan "restored" event.
     */
    public function restored(Clan $clan): void
    {
        //
    }

    /**
     * Handle the Clan "force deleted" event.
     */
    public function forceDeleted(Clan $clan): void
    {
        //
    }
}
