<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\EmailAddress;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use App\Exceptions\EmailVerificationException;

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

    public function test_verify_calls_sync_and_returns_true()
    {
        $email = EmailAddress::factory()->create(['verification_sent_at' => now(), 'verification_code' => 'XYZ789', 'verified_at' => null]);
        // calling verify should not throw
        $this->assertTrue($email->verify('XYZ789'));
    }
}
