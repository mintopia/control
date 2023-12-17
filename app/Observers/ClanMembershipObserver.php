<?php

namespace App\Observers;

use App\Models\ClanMembership;
use App\Models\SeatingPlan;
use Illuminate\Support\Collection;

class ClanMembershipObserver
{
    public function saved(ClanMembership $clanMembership): void
    {
        if ($clanMembership->isDirty('user_id')) {
            $plans = SeatingPlan::whereHas('seats.ticket.user.clanMemberships', function ($query) use ($clanMembership) {
                $query->whereId($clanMembership->id);
            })->get();
            foreach ($plans as $plan) {
                $plan->updateRevision();
            }
        }
    }
    /**
     * Handle the ClanMembership "created" event.
     */
    public function created(ClanMembership $clanMembership): void
    {
        //
    }

    /**
     * Handle the ClanMembership "updated" event.
     */
    public function updated(ClanMembership $clanMembership): void
    {
        //
    }

    public function deleting(ClanMembership $clanMembership): void
    {
        $plans = SeatingPlan::whereHas('seats.ticket.user.clanMemberships', function ($query) use ($clanMembership) {
            $query->whereId($clanMembership->id);
        })->get();
        foreach($plans as $plan) {
            $plan->delayedRevisionUpdate();
        }
    }

    /**
     * Handle the ClanMembership "restored" event.
     */
    public function restored(ClanMembership $clanMembership): void
    {
        //
    }

    /**
     * Handle the ClanMembership "force deleted" event.
     */
    public function forceDeleted(ClanMembership $clanMembership): void
    {
        //
    }
}
