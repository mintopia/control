<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\EmailAddress
 *
 * @property int $id
 * @property int $user_id
 * @property string $email
 * @property string|null $verification_code
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress query()
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereVerificationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereVerifiedAt($value)
 * @mixin \Eloquent
 */
class EmailAddress extends Model
{
    use HasFactory, ToString;

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    protected function toStringName(): string
    {
        return $this->email;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
