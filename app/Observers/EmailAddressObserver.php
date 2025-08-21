<?php

namespace App\Observers;

use App\Models\EmailAddress;

class EmailAddressObserver
{
    /**
     * Handle the EmailAddress "created" event.
     */
    public function created(EmailAddress $emailAddress): void
    {
        $user = $emailAddress->user;
        if ($user) {
            $primaryEmailRelation = $user->primaryEmail();
            if ($primaryEmailRelation->getResults() === null) {
                $primaryEmailRelation->associate($emailAddress);
                $user->save();
            }
        }
    }

    /**
     * Handle the EmailAddress "updated" event.
     */
    public function updated(EmailAddress $emailAddress): void
    {
        //
    }

    /**
     * Handle the EmailAddress "deleted" event.
     */
    public function deleted(EmailAddress $emailAddress): void
    {
        //
    }

    /**
     * Handle the EmailAddress "restored" event.
     */
    public function restored(EmailAddress $emailAddress): void
    {
        //
    }

    /**
     * Handle the EmailAddress "force deleted" event.
     */
    public function forceDeleted(EmailAddress $emailAddress): void
    {
        //
    }
}
