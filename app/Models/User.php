<?php

namespace App\Models;

use App\Jobs\SyncTicketsForEmailJob;
use App\Jobs\SyncTicketsForUserJob;
use App\Models\Traits\ToString;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClanMembership> $clanMemberships
 * @property-read int|null $clan_memberships_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Clan> $clans
 * @property-read int|null $clans_count
 * @property \Illuminate\Support\Carbon|null $tickets_synced_at
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTicketsSyncedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, ToString;

    protected ?Collection $pickableTickets = null;

    protected $casts = [
        'tickets_synced_at' => 'datetime',
        'terms_agreed_at' => 'datetime',
    ];

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

    public function clanMemberships(): HasMany
    {
        return $this->hasMany(ClanMembership::class);
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

    public function avatarUrl(): string
    {
        foreach ($this->accounts as $acc) {
            if ($acc->avatar_url) {
                return $acc->avatar_url;
            }
        }
        return 'https://gravatar.com/avatar/' . hash('sha256', $this->primaryEmail->email);
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

        $this->pickableTickets = $tickets->filter(function(Ticket $ticket) {
            return $ticket->canPickSeat();
        });
        return $this->pickableTickets;
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->primaryEmail->email ?? null;
            },
        );
    }
}
