<?php

namespace App\Policies;

use App\Models\EmailAddress;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmailAddressPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmailAddress $emailAddress): bool
    {
        return ($user->id === $emailAddress->user_id) || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmailAddress $emailAddress): bool
    {
        return ($user->id === $emailAddress->user_id) || $user->hasRole('admin');;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmailAddress $emailAddress): bool
    {
        return ($user->id === $emailAddress->user_id) || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EmailAddress $emailAddress): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EmailAddress $emailAddress): bool
    {
        return $user->hasRole('admin');
    }
}
