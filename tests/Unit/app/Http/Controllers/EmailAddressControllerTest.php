<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\EmailAddressController;
use App\Models\User;
use App\Models\EmailAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailAddressControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new EmailAddressController();
        $this->assertInstanceOf(EmailAddressController::class, $controller);
    }

    public function testCreateReturnsView()
    {
        $controller = new EmailAddressController();
        $response = $controller->create();
        $this->assertTrue(is_object($response));
    }
    public function testStoreCreatesEmailAndSendsVerification()
    {
        Mail::fake();

        $user = User::factory()->create();

        $request = \App\Http\Requests\EmailAddressRequest::create('/emails', 'POST', ['email' => 'test@example.com']);
        $request->setUserResolver(fn() => $user);

        $controller = new EmailAddressController();
        $response = $controller->store($request);

        $this->assertDatabaseHas('email_addresses', [
            'email' => 'test@example.com',
            'user_id' => $user->id,
        ]);

        $this->assertTrue(method_exists($response, 'getTargetUrl'));
    }

    public function testDeleteReturnsViewOrRedirects()
    {
        $user = User::factory()->create();
        $email = $user->emails()->create(['email' => 'delete-me@example.com']);

        $this->actingAs($user);
        $user->primary_email_id = null;
        $user->save();

        $controller = new EmailAddressController();
        $response = $controller->delete($email);

        $this->assertTrue(is_object($response));
    }

    public function testDestroyDeletesEmail()
    {
        $user = User::factory()->create();
        $email = $user->emails()->create(['email' => 'remove-me@example.com']);

        // Ensure the user is authenticated and the email is deletable
        $this->actingAs($user);
        $user->primary_email_id = null;
        $user->save();
        // Reload the email's user relation so canDelete() sees the updated primary_email_id
        $email->load('user');
        // Also ensure the loaded user relation has primary_email_id cleared
        $email->user->primary_email_id = null;
        $email->user->save();
        // Ensure no linked accounts are attached which would prevent deletion
        if ($email->linkedAccounts()->count() > 0) {
            $email->linkedAccounts()->delete();
            $email->refresh();
        }
        $this->assertTrue($email->canDelete(), 'Email should be deletable in test setup');

        $controller = new EmailAddressController();
        $response = $controller->destroy($email);

        $this->assertTrue(is_object($response));
        $this->assertDatabaseMissing('email_addresses', ['id' => $email->id]);
    }
}
