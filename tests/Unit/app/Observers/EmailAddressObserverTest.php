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
}
