<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\LinkedService
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
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService query()
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService whereRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService whereRefreshTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService whereService($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LinkedService whereUserId($value)
 * @mixin \Eloquent
 */
class LinkedService extends Model
{
    use HasFactory, ToString;

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'refresh_token_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
