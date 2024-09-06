<?php

namespace App\Models;

use App\Exceptions\EmailVerificationException;
use App\Jobs\SyncTicketsForEmailJob;
use App\Mail\VerifyEmail;
use App\Models\Traits\ToString;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Mail;
use function App\makeCode;

/**
 * App\Models\EmailAddress
 *
 * @mixin IdeHelperEmailAddress
 * @property int $id
 * @property int $user_id
 * @property string $email
 * @property string|null $verification_code
 * @property \Illuminate\Support\Carbon|null $verification_sent_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LinkedAccount> $linkedAccounts
 * @property-read int|null $linked_accounts_count
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
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereVerificationSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmailAddress whereVerifiedAt($value)
 * @mixin \Eloquent
 */
class EmailAddress extends Model
{
    use HasFactory, ToString;

    protected $casts = [
        'verified_at' => 'datetime',
        'verification_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sendVerificationCode(): void
    {
        $this->verification_code = makeCode(6);
        $this->verification_sent_at = Carbon::now();
        $this->save();
        Mail::to($this->email)->send(new VerifyEmail($this));
    }

    public function canDelete(): bool
    {
        if ($this->linkedAccounts()->count() > 0) {
            return false;
        }
        if ($this->id === $this->user->primary_email_id) {
            return false;
        }

        return true;
    }

    public function linkedAccounts(): HasMany
    {
        return $this->hasMany(LinkedAccount::class);
    }

    public function verify(string $code): bool
    {
        $this->checkCode($code);
        $this->verified_at = Carbon::now();
        $this->save();
        $this->syncTickets();
        return true;
    }

    public function checkCode(string $code): bool
    {
        if (Carbon::now() > $this->getVerificationExpiry()) {
            throw new EmailVerificationException('The verification code has expired');
        }
        if ($code !== $this->verification_code) {
            throw new EmailVerificationException('The verification code is incorrect');
        }
        return true;
    }

    public function getVerificationExpiry(): Carbon
    {
        return $this->verification_sent_at->addDays(2);
    }

    public function syncTickets(bool $sync = false): void
    {
        if ($this->verified_at === null) {
            return;
        }

        $method = 'dispatch';
        if ($sync) {
            $method = 'dispatchSync';
        }
        SyncTicketsForEmailJob::$method($this);
    }

    protected function toStringName(): string
    {
        return $this->email;
    }
}
