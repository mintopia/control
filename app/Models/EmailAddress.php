<?php

namespace App\Models;

use App\Exceptions\EmailVerificationException;
use App\Jobs\SyncTicketsForEmailJob;
use App\Mail\VerifyEmail;
use App\Models\Traits\ToString;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
 * @property-read Collection<int, LinkedAccount> $linkedAccounts
 * @property-read int|null $linked_accounts_count
 * @property-read User $user
 * @method static Builder|EmailAddress newModelQuery()
 * @method static Builder|EmailAddress newQuery()
 * @method static Builder|EmailAddress query()
 * @method static Builder|EmailAddress whereCreatedAt($value)
 * @method static Builder|EmailAddress whereEmail($value)
 * @method static Builder|EmailAddress whereId($value)
 * @method static Builder|EmailAddress whereUpdatedAt($value)
 * @method static Builder|EmailAddress whereUserId($value)
 * @method static Builder|EmailAddress whereVerificationCode($value)
 * @method static Builder|EmailAddress whereVerificationSentAt($value)
 * @method static Builder|EmailAddress whereVerifiedAt($value)
 * @mixin Eloquent
 */
class EmailAddress extends Model
{
    use HasFactory;
    use ToString;

    protected $fillable = [
        'user_id',
        'email',
        'verification_code',
        'verification_sent_at',
        'verified_at',
        'created_at',
        'updated_at'
    ];

    protected $hidden = [
        'verification_code',
    ];

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
