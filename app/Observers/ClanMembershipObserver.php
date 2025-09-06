<?php

namespace App\Observers;

use App\Models\ClanMembership;
use App\Models\SeatingPlan;

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

    public function deleting(ClanMembership $clanMembership): void
    {
        $plans = SeatingPlan::whereHas('seats.ticket.user.clanMemberships', function ($query) use ($clanMembership) {
            $query->whereId($clanMembership->id);
        })->get();
        foreach ($plans as $plan) {
            $plan->delayedRevisionUpdate();
        }
    }
}
