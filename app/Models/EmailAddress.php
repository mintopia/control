<?php

namespace App\Models;

use App\Models\Traits\ToString;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
 * @property \Illuminate\Support\Carbon|null $verification_sent_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LinkedAccount> $linkedAccounts
 * @property-read int|null $linked_accounts_count
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereVerificationSentAt($value)
 * @mixin \Eloquent
 */
class EmailAddress extends Model
{
    use HasFactory, ToString;

    protected $casts = [
        'verified_at' => 'datetime',
        'verification_sent_at' => 'datetime',
    ];

    protected function toStringName(): string
    {
        return $this->email;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function linkedAccounts(): HasMany
    {
        return $this->hasMany(LinkedAccount::class);
    }

    public function sendVerificationCode(): void
    {
        $code = '';
        $chars = 'ABCDEFGHJKLMNPQRSTUVQXYZ23456789';
        for ($i = 0; $i < 6; $i++) {
            $code .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        $this->verification_code = $code;
        $this->verification_sent_at = Carbon::now();
        $this->save();
        // TODO: Dispatch Email
    }

    public function canDelete(): bool
    {
        if ($this->linked_accounts_count > 0) {
            return false;
        }
        if ($this->id === $this->user->primary_email_id) {
            return false;
        }

        return true;
    }
}
