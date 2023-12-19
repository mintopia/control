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
 * @mixin IdeHelperEmailAddress
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
