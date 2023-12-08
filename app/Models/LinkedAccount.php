<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\LinkedAccount
 *
 * @property int $id
 * @property int $user_id
 * @property string $service
 * @property string|null $external_id
 * @property string|null $name
 * @property string|null $avatar_url
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property \Illuminate\Support\Carbon|null $refresh_token_expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereRefreshTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereService($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereUserId($value)
 * @property string|null $access_token_expires_at
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereAccessTokenExpiresAt($value)
 * @property int|null $email_address_id
 * @property-read \App\Models\EmailAddress|null $email
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereEmailAddressId($value)
 * @property int|null $social_provider_id
 * @property-read \App\Models\SocialProvider|null $provider
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedAccount whereSocialProviderId($value)
 * @mixin \Eloquent
 */
class LinkedAccount extends Model
{
    use HasFactory, ToString;

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
}
