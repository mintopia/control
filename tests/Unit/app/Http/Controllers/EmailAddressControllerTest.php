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
use Laravel\Sanctum\Sanctum;
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

        Sanctum::actingAs($user);
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
        Sanctum::actingAs($user);
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

    public function testStoreRouteCreatesEmailAndRedirects()
    {
        Mail::fake();

        $user = User::factory()->create();

        // Disable middleware so we can exercise the controller logic in this test environment
        $this->withoutMiddleware();

        Sanctum::actingAs($user);

        $response = $this->post(route('emails.store'), ['email' => 'web-test@example.com']);

        $response->assertStatus(302, $response->getContent());
        $response->assertSessionHasNoErrors();

        $location = $response->headers->get('Location');
        $this->assertIsString($location);
        $this->assertStringContainsString('/profile/emails', $location);
        $this->assertStringContainsString('/verify', $location);

        $this->assertDatabaseHas('email_addresses', [
            'email' => 'web-test@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function testVerifyProcessControllerMethodVerifiesEmail()
    {
        $user = User::factory()->create();

        $email = EmailAddress::factory()->create([
            'user_id' => $user->id,
            'verification_code' => 'CODE123',
            'verification_sent_at' => now(),
        ]);

        $request = \App\Http\Requests\EmailVerifyRequest::create('/emails/' . $email->id . '/verify', 'POST', ['code' => 'CODE123']);
        $request->setUserResolver(fn() => $user);

        $controller = new \App\Http\Controllers\EmailAddressController();
        $response = $controller->verify_process($request, $email);

        $this->assertTrue(method_exists($response, 'getTargetUrl'));
        $this->assertNotNull($email->fresh()->verified_at);
    }

    //FIXME These do not work properly
    public function testVerifyProcessRouteVerifiesEmail()
    {
        $user = User::factory()->create();

        $email = EmailAddress::factory()->create([
            'user_id' => $user->id,
            'verification_code' => 'CODE123',
            'verification_sent_at' => now(),
        ]);

        // Disable middleware so we can exercise the controller logic in this test environment
        $this->withoutMiddleware();

        // Show the underlying exception during tests so we get a full stack trace instead of a 500 response
        $this->withoutExceptionHandling();

        // Ensure route-model binding returns a model instance (some test flows surface the raw id)
        \Illuminate\Support\Facades\Route::bind('emailaddress', fn($value) => EmailAddress::findOrFail($value));

        Sanctum::actingAs($user);

        $response = $this->post(route('emails.verify.process', $email->id), ['code' => 'CODE123']);

        $response->assertStatus(302, $response->getContent());
        $response->assertRedirect(route('user.profile'));
        $response->assertSessionHasNoErrors();

        $this->assertNotNull($email->fresh()->verified_at);
    }
}
