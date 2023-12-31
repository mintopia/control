<?php

namespace App\Policies;

use App\Models\EmailAddress;
use App\Models\User;

class EmailAddressPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmailAddress $emailAddress): bool
    {
        return $user->id === $emailAddress->user_id;
    }
}
