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

    public function test_send_verification_code_saves_and_sends()
    {
        Mail::fake();
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['user_id' => $user->id, 'verified_at' => null]);

        $email->sendVerificationCode();

        $this->assertNotNull($email->verification_code);
        $this->assertNotNull($email->verification_sent_at);
        Mail::assertSent(VerifyEmail::class);
    }

    public function test_can_delete_false_when_linked_accounts_or_primary()
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

    public function test_check_code_throws_on_expired_or_wrong()
    {
        $email = EmailAddress::factory()->create(['verification_sent_at' => now()->subDays(3), 'verification_code' => 'ABC123']);
        $this->expectException(EmailVerificationException::class);
        $email->checkCode('ABC123');
    }

    public function test_check_code_throws_on_incorrect_code()
    {
        $email = EmailAddress::factory()->create(['verification_sent_at' => now(), 'verification_code' => 'ABC123']);
        $this->expectException(EmailVerificationException::class);
        $email->checkCode('WRONG');
    }

    public function test_verify_calls_sync_and_returns_true()
    {
        $email = EmailAddress::factory()->create(['verification_sent_at' => now(), 'verification_code' => 'XYZ789', 'verified_at' => null]);
        // calling verify should not throw
        $this->assertTrue($email->verify('XYZ789'));
    }

    public function test_sync_tickets_does_nothing_when_not_verified()
    {
        Bus::fake();
        $email = EmailAddress::factory()->create(['verified_at' => null]);
        $email->syncTickets();
        Bus::assertNotDispatched(SyncTicketsForEmailJob::class);
    }

    public function test_sync_tickets_dispatches_job_when_verified()
    {
        Bus::fake();
        $email = EmailAddress::factory()->create(['verified_at' => now()]);
        $email->syncTickets(false);
        Bus::assertDispatched(SyncTicketsForEmailJob::class);
    }

    public function test_sync_tickets_dispatches_sync_when_requested()
    {
        Bus::fake();
        $email = EmailAddress::factory()->create(['verified_at' => now()]);
        $email->syncTickets(true);
        Bus::assertDispatched(SyncTicketsForEmailJob::class);
    }
}
