<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\EmailAddressObserver;
use App\Models\EmailAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailAddressObserverTest extends TestCase
{
    use RefreshDatabase;

    public function testCreatedAssociatesPrimaryEmailIfMissing()
    {
        $user = User::factory()->create();

        // Create (persist) an email address for the user so observer->created() can operate on it
        $email = EmailAddress::factory()->create(['user_id' => $user->id]);

        $observer = new EmailAddressObserver();
        $observer->created($email);

        // The observer should have associated the email as the user's primary_email_id
        $user->refresh();
        $this->assertEquals($email->id, $user->primary_email_id);
    }

    public function testCreatedDoesNothingWhenUserIsNull()
    {
        // Build an email address model instance without persisting (DB enforces non-null user_id)
        $email = EmailAddress::factory()->make(['user_id' => null]);

        $observer = new EmailAddressObserver();
        // Should return early and not throw
        $observer->created($email);

        $this->assertNull($email->user);
    }

    public function testUpdatedIsPendingImplementation()
    {
        $this->markTestSkipped('EmailAddressObserver::updated not implemented yet');
    }

    public function testDeletedIsPendingImplementation()
    {
        $this->markTestSkipped('EmailAddressObserver::deleted not implemented yet');
    }

    public function testRestoredIsPendingImplementation()
    {
        $this->markTestSkipped('EmailAddressObserver::restored not implemented yet');
    }

    public function testForceDeletedIsPendingImplementation()
    {
        $this->markTestSkipped('EmailAddressObserver::forceDeleted not implemented yet');
    }
}
