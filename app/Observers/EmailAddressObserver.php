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
        if ($emailAddress->user === null) {
            return;
        }
        if ($emailAddress->user->primaryEmail === null) {
            $emailAddress->user->primaryEmail()->associate($emailAddress);
            $user->save();
        }
    }
}
