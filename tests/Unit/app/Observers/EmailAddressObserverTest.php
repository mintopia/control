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


    // NOTE The following tests assert empty observer handlers are currently no-ops.
    public function testUpdatedIsPendingImplementation()
    {
        $user = User::factory()->create();

        // Ensure the user has a primary email already
        $primary = EmailAddress::factory()->create(['user_id' => $user->id]);
        $user->primary_email_id = $primary->id;
        $user->save();

        // Another email address to pass to the observer
        $other = EmailAddress::factory()->create(['user_id' => $user->id]);

        // Snapshot current primary after any creation observers
        $before = $user->fresh()->primary_email_id;

        $observer = new EmailAddressObserver();
        $observer->updated($other);

        // updated() is currently a no-op; primary_email_id should remain unchanged
        $this->assertEquals($before, $user->fresh()->primary_email_id);
    }

    public function testDeletedIsPendingImplementation()
    {
        $user = User::factory()->create();

        // Ensure the user has a primary email already
        $primary = EmailAddress::factory()->create(['user_id' => $user->id]);
        $user->primary_email_id = $primary->id;
        $user->save();

        $other = EmailAddress::factory()->create(['user_id' => $user->id]);

        // Snapshot current primary after any creation observers
        $before = $user->fresh()->primary_email_id;

        $observer = new EmailAddressObserver();
        $observer->deleted($other);

        // deleted() is currently a no-op; primary_email_id should remain unchanged
        $this->assertEquals($before, $user->fresh()->primary_email_id);
    }

    public function testRestoredIsPendingImplementation()
    {
        $user = User::factory()->create();

        // Ensure the user has a primary email already
        $primary = EmailAddress::factory()->create(['user_id' => $user->id]);
        $user->primary_email_id = $primary->id;
        $user->save();

        $other = EmailAddress::factory()->create(['user_id' => $user->id]);

        // Snapshot current primary after any creation observers
        $before = $user->fresh()->primary_email_id;

        $observer = new EmailAddressObserver();
        $observer->restored($other);

        // restored() is currently a no-op; primary_email_id should remain unchanged
        $this->assertEquals($before, $user->fresh()->primary_email_id);
    }

    public function testForceDeletedIsPendingImplementation()
    {
        $user = User::factory()->create();

        // Ensure the user has a primary email already
        $primary = EmailAddress::factory()->create(['user_id' => $user->id]);
        $user->primary_email_id = $primary->id;
        $user->save();

        $other = EmailAddress::factory()->create(['user_id' => $user->id]);

        // Snapshot current primary after any creation observers
        $before = $user->fresh()->primary_email_id;

        $observer = new EmailAddressObserver();
        $observer->forceDeleted($other);

        // forceDeleted() is currently a no-op; primary_email_id should remain unchanged
        $this->assertEquals($before, $user->fresh()->primary_email_id);
    }
}
