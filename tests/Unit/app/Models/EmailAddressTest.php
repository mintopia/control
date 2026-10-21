<?php

namespace Tests\Unit\app\Models;

use App\Exceptions\EmailVerificationException;
use App\Jobs\SyncTicketsForEmailJob;
use App\Mail\VerifyEmail;
use App\Models\EmailAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailAddressTest extends TestCase
{
    use RefreshDatabase;

    public function testSendVerificationCodeSavesAndSends()
    {
        Mail::fake();
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['user_id' => $user->id, 'verified_at' => null]);

        $email->sendVerificationCode();

        $this->assertNotNull($email->verification_code);
        $this->assertNotNull($email->verification_sent_at);
        Mail::assertSent(VerifyEmail::class);
    }

    public function testCanDeleteFalseWhenLinkedAccountsOrPrimary()
    {
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['user_id' => $user->id]);

        // make it primary
        $user->primary_email_id = $email->id;
        $user->save();

        $this->assertFalse($email->canDelete());

        // Unset primary and add a linked account to make canDelete false
        $user->primary_email_id = null;
        $user->save();

        $linked = $email->linkedAccounts()->create(['user_id' => $user->id]);
        $this->assertFalse($email->canDelete());
    }

    public function testCheckCodeThrowsOnExpiredOrWrong()
    {
        $email = EmailAddress::factory()->create(['verification_sent_at' => now()->subDays(3), 'verification_code' => 'ABC123']);
        $this->expectException(EmailVerificationException::class);
        $email->checkCode('ABC123');
    }

    public function testCheckCodeThrowsOnIncorrectCode()
    {
        $email = EmailAddress::factory()->create(['verification_sent_at' => now(), 'verification_code' => 'ABC123']);
        $this->expectException(EmailVerificationException::class);
        $email->checkCode('WRONG');
    }

    public function testVerifyCallsSyncAndReturnsTrue()
    {
        $email = EmailAddress::factory()->create(['verification_sent_at' => now(), 'verification_code' => 'XYZ789', 'verified_at' => null]);
        // calling verify should not throw
        $this->assertTrue($email->verify('XYZ789'));
    }

    public function testSyncTicketsDoesNothingWhenNotVerified()
    {
        Bus::fake();
        $email = EmailAddress::factory()->create(['verified_at' => null]);
        $email->syncTickets();
        Bus::assertNotDispatched(SyncTicketsForEmailJob::class);
    }

    public function testSyncTicketsDispatchesJobWhenVerified()
    {
        Bus::fake();
        $email = EmailAddress::factory()->create(['verified_at' => now()]);
        $email->syncTickets(false);
        Bus::assertDispatched(SyncTicketsForEmailJob::class);
    }

    public function testSyncTicketsDispatchesSyncWhenRequested()
    {
        Bus::fake();
        $email = EmailAddress::factory()->create(['verified_at' => now()]);
        $email->syncTickets(true);
        Bus::assertDispatched(SyncTicketsForEmailJob::class);
    }
}
