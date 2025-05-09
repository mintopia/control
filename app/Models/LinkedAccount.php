<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\LinkedAccount
 *
 * @mixin IdeHelperLinkedAccount
 * @property int $id
 * @property int $user_id
 * @property int|null $email_address_id
 * @property int|null $social_provider_id
 * @property string|null $external_id
 * @property string|null $name
 * @property string|null $avatar_url
 * @property mixed|null $access_token
 * @property mixed|null $refresh_token
 * @property string|null $access_token_expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\EmailAddress|null $email
 * @property-read \App\Models\SocialProvider|null $provider
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereAccessTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereEmailAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereSocialProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereUserId($value)
 * @mixin \Eloquent
 */
class LinkedAccount extends Model
{
    use HasFactory;
    use ToString;

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'refresh_token_expires_at' => 'datetime',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(EmailAddress::class, 'email_address_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SocialProvider::class, 'social_provider_id');
    }

    public function canDelete(): bool
    {
        // A user must have 1 linked account for auth, regardless!
        if ($this->user->accounts()->count() === 1) {
            return false;
        }

        // If this isn't for auth, it can be deleted
        if (!$this->provider->auth_enabled) {
            return true;
        }

        // If it is for auth, they must have at least one other auth
        return $this->user->accounts()->whereHas('provider', function ($query) {
                $query->where('auth_enabled', true);
        })->count() > 1;
    }
}
