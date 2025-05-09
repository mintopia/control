<?php

namespace App\Models;

use App\Models\Traits\ToString;
use App\Services\DiscordApi;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;

/**
 * App\Models\User
 *
 * @mixin IdeHelperUser
 * @property int $id
 * @property string $nickname
 * @property string|null $name
 * @property string|null $avatar
 * @property \Illuminate\Support\Carbon|null $terms_agreed_at
 * @property int $first_login
 * @property \Illuminate\Support\Carbon|null $last_login
 * @property int $suspended
 * @property int|null $primary_email_id
 * @property \Illuminate\Support\Carbon|null $tickets_synced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LinkedAccount> $accounts
 * @property-read int|null $accounts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClanMembership> $clanMemberships
 * @property-read int|null $clan_memberships_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmailAddress> $emails
 * @property-read int|null $emails_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\EmailAddress|null $primaryEmail
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFirstLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNickname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePrimaryEmailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSuspended($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTermsAgreedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTicketsSyncedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use ToString;

    protected ?Collection $pickableTickets = null;

    protected $casts = [
        'tickets_synced_at' => 'datetime',
        'terms_agreed_at' => 'datetime',
        'last_login' => 'datetime',
    ];

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

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function hasRole(string|Role $role): bool
    {
        if ($role instanceof Role) {
            $role = $role->code;
        }
        return (bool)$this->roles()->whereCode($role)->count();
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($role instanceof Role) {
                $role = $role->code;
            }
            if ($this->roles()->whereCode($role)->count()) {
                return true;
            }
        }
        return false;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function avatarUrl(): string
    {
        foreach ($this->accounts as $acc) {
            if ($acc->avatar_url) {
                return $acc->avatar_url;
            }
        }
        if ($this->primaryEmail) {
            $toHash = $this->primaryEmail->email;
        } else {
            $toHash = $this->nickname;
        }
        $hash = hash('sha256', $toHash);
        return "https://gravatar.com/avatar/{$hash}?d=retro";
    }

    public function syncTickets(bool $sync = false, bool $force = false): void
    {
        if (!$force && $this->tickets_synced_at && $this->tickets_synced_at > Carbon::now()->subMinutes(5)) {
            return;
        }

        $this->tickets_synced_at = Carbon::now();
        $this->save();
        foreach ($this->emails as $email) {
            $email->syncTickets($sync);
        }
    }

    public function getPickableTickets(Event $event): Collection
    {
        if ($this->pickableTickets !== null) {
            return $this->pickableTickets;
        }

        $clanIds = $this->clanMemberships()->whereHas('role', function ($query) {
            $query->whereIn('code', ['leader', 'seatmanager']);
        })->pluck('clan_id');

        // Get eligible tickets for seating
        $query = Ticket::whereEventId($event->id)->whereUserId($this->id);
        $query = $query->orWhereHas('user', function ($query) use ($clanIds) {
            $query->whereHas('clanMemberships', function ($query) use ($clanIds) {
                $query->whereIn('clan_id', $clanIds);
            });
        });
        $tickets = $query
            ->with(['user' => function ($query) {
                $query->orderBy('nickname', 'ASC');
            }, 'type', 'seat'])
            ->get();

        $this->pickableTickets = $tickets->filter(function (Ticket $ticket) {
            return $ticket->canPickSeat();
        });
        return $this->pickableTickets;
    }

    public function clanMemberships(): HasMany
    {
        return $this->hasMany(ClanMembership::class);
    }

    protected function toStringName(): string
    {
        return $this->nickname;
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->primaryEmail->email ?? null;
            },
        );
    }

    protected function getDiscordAccount()
    {
        return $this->accounts()->whereHas('provider', function ($query) {
            $query->whereCode('discord');
        })->first();
    }

    public function addDiscordRole(string $roleId): bool
    {
        $account = $this->getDiscordAccount();
        if (!$account) {
            return false;
        }
        $api = resolve(DiscordApi::class);
        if (!$api) {
            return false;
        }
        try {
            $api->addRoleToMember($roleId, $account->external_id);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function removeDiscordRole(string $roleId): bool
    {
        $account = $this->getDiscordAccount();
        if (!$account) {
            return false;
        }
        $api = resolve(DiscordApi::class);
        if (!$api) {
            return false;
        }
        try {
            $api->removeRoleFromMember($roleId, $account->external_id);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function syncDiscordRoles(): void
    {
        Log::debug("{$this}: Queued sync of discord roles");
        Artisan::queue('control:sync-discord-roles', [
            'user' => $this->id,
        ]);
    }

    public function allowedSeatGroup(SeatGroup $group): bool
    {
        foreach ($group->assignments as $assignment) {
            switch ($assignment->assignment_type) {
                case 'user':
                    if ($assignment->assignment_type_id == $this->id) {
                        return true;
                    }
                    break;
                case 'clan':
                    foreach ($this->clanMemberships as $clanMembership) {
                        if ($clanMembership->clan->id == $assignment->assignment_type_id) {
                            return true;
                        }
                    }
                    break;
                case 'ticket_type':
                    foreach ($this->tickets as $ticket) {
                        if ($ticket->type->id == $assignment->assignment_type_id) {
                            return true;
                        }
                    }
                    break;
            }
        }
        return false;
    }
}
