<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $nickname
 * @property int|null $primary_email_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmailAddress> $emails
 * @property-read int|null $emails_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\EmailAddress|null $primaryEmail
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePrimaryEmailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LinkedAccount> $accounts
 * @property-read int|null $accounts_count
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, ToString;

    protected function toStringName(): string
    {
        return $this->nickname;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(EmailAddress::class);
    }

    public function primaryEmail(): BelongsTo
    {
        return $this->belongsTo(EmailAddress::class, 'primary_email_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(LinkedAccount::class);
    }

    public function hasRole(string|Role $role): bool
    {
        if ($role instanceof Role) {
            $role = $role->code;
        }
        return (bool)$this->roles()->whereCode($role)->count();
    }

    public static function fromDiscord(\Laravel\Socialite\Two\User $discordUser): ?User
    {
        DB::transaction(function() use ($discordUser) {
            $account = LinkedAccount::whereExternalId($discordUser->id)->with('user')->first();
            if (!$account) {
                $exists = EmailAddress::whereEmail($discordUser->email)->count();
                if ($exists > 0) {
                    throw new \Exception('Email address is already in use');
                }
                $user = new User();
                $user->nickname = $discordUser->nickname;
                $account = new LinkedAccount();
                $account->service = 'discord';
                $account->external_id = $discordUser->id;
                $user->save();
                $account->user()->associate($user);
                $account->save();

                // If we're the first user, assign the admin role
                if (User::count() === 1) {
                    $role = Role::whereCode('admin')->first();
                    if ($role) {
                        $user->roles()->attach($role);
                    }
                }
            }

            // Update user based on discord data
            $account->user->nickname = $discordUser->nickname;

            // Update linked account based on discord data
            $account->avatar_url = $discordUser->avatar;
            $account->refresh_token = $discordUser->refreshToken;
            $account->access_token = $discordUser->token;
            $account->name = $discordUser->name;
            $account->access_token_expires_at = Carbon::now()->addSeconds($discordUser->expiresIn);

            // Update email based on discord data
            $email = EmailAddress::whereEmail($discordUser->email)->whereUserId($account->user->id)->first();
            if (!$email) {
                $email = new EmailAddress();
                $email->email = $discordUser->email;
                $email->verified_at = Carbon::now();
                $email->user()->associate($account->user);
                $email->save();
            }

            // Set user's primary email
            if (!$account->user->primaryEmail) {
                $account->user->primaryEmail()->associate($email);
            }

            // Save account and user
            $account->user->save();
            $account->save();
        });

        $account = LinkedAccount::whereExternalId($discordUser->id)->with('user')->first();
        return $account->user;
    }
}
