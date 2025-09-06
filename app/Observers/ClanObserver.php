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

    public function saving(Clan $clan): void
    {
        if (!$clan->invite_code) {
            $clan->generateCode();
        }
        $clan->code = makePermalink($clan->name);
    }
}
