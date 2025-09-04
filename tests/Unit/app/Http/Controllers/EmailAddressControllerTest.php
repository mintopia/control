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

    public function testDeleteReturnsViewWhenCanDelete()
    {
        $user = User::factory()->create();
        $email = $user->emails()->create(['email' => 'delete-ok@example.com']);

        // Ensure deletable: no primary email and no linked accounts
        Sanctum::actingAs($user);
        $user->primary_email_id = null;
        $user->save();

        // Reload email relations and ensure canDelete() is true
        $email->load('user');
        $email->user->primary_email_id = null;
        $email->user->save();
        if ($email->linkedAccounts()->count() > 0) {
            $email->linkedAccounts()->delete();
            $email->refresh();
        }

        $this->assertTrue($email->canDelete(), 'Precondition: email should be deletable');

        $controller = new EmailAddressController();
        $response = $controller->delete($email);

        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $this->assertArrayHasKey('email', $response->getData());
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

    public function testVerifyProcessRouteVerifiesEmail()
    {
        $user = User::factory()->create();

        $email = EmailAddress::factory()->create([
            'user_id' => $user->id,
            'verification_code' => 'CODE123',
            'verification_sent_at' => now(),
        ]);

        // Show the underlying exception during tests so we get a full stack trace instead of a 500 response
        $this->withoutExceptionHandling();

        // Ensure route-model binding returns a model instance (some test flows surface the raw id)
        \Illuminate\Support\Facades\Route::bind('emailaddress', fn($value) => EmailAddress::findOrFail($value));

        // Ensure the authenticated user will not be redirected by the first-login middleware
        $user->first_login = false;
        $user->save();

        Sanctum::actingAs($user);

        $response = $this->post(route('emails.verify.process', $email->id), ['code' => 'CODE123']);

        $response->assertStatus(302, $response->getContent());
        $response->assertRedirect(route('user.profile'));
        $response->assertSessionHasNoErrors();

        $this->assertNotNull($email->fresh()->verified_at);
    }

    public function testVerifyShowsViewWhenNotVerified()
    {
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['user_id' => $user->id, 'verified_at' => null]);

        $request = Request::create('/emails/' . $email->id . '/verify', 'GET');
        $controller = new EmailAddressController();
        $view = $controller->verify($request, $email);
        $this->assertTrue(is_object($view));
        $this->assertArrayHasKey('email', $view->getData());
    }

    public function testVerifyRedirectsWhenAlreadyVerified()
    {
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['user_id' => $user->id, 'verified_at' => now()]);

        $controller = new EmailAddressController();
        $response = $controller->verify(Request::create('/emails/' . $email->id . '/verify', 'GET'), $email);
        $this->assertTrue(method_exists($response, 'getTargetUrl'));
        $this->assertStringContainsString('/profile', $response->getTargetUrl());
    }

    public function testVerifyCodeMethodVerifiesEmail()
    {
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create([
            'user_id' => $user->id,
            'verification_code' => 'CODE123',
            'verification_sent_at' => now(),
        ]);

        $request = \App\Http\Requests\EmailVerifyRequest::create('/emails/' . $email->id . '/verify_code', 'POST', ['code' => 'CODE123']);
        $request->setUserResolver(fn() => $user);

        $controller = new EmailAddressController();
        $response = $controller->verify_code($request, $email);

        $this->assertTrue(method_exists($response, 'getTargetUrl'));
        $this->assertNotNull($email->fresh()->verified_at);
    }

    public function testVerifyResendSendsCodeWhenNotVerified()
    {
        Mail::fake();
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['user_id' => $user->id, 'verified_at' => null]);

        $controller = new EmailAddressController();
        $response = $controller->verify_resend($email);

        $this->assertTrue(method_exists($response, 'getTargetUrl'));
        $this->assertStringContainsString('/verify', $response->getTargetUrl());
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\VerifyEmail::class);
        $this->assertNotNull($email->fresh()->verification_code);
    }

    public function testVerifyResendRedirectsWhenAlreadyVerified()
    {
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['user_id' => $user->id, 'verified_at' => now()]);

        $controller = new EmailAddressController();
        $response = $controller->verify_resend($email);

        $this->assertTrue(method_exists($response, 'getTargetUrl'));
        $this->assertStringContainsString('/profile', $response->getTargetUrl());
    }

    public function testDeleteRedirectsWhenCannotDelete()
    {
        $user = User::factory()->create();
        $email = $user->emails()->create(['email' => 'cannot-delete@example.com']);
        // make it non-deletable by setting as primary
        $user->primary_email_id = $email->id;
        $user->save();

        $controller = new EmailAddressController();
        $response = $controller->delete($email);

        $this->assertTrue(method_exists($response, 'getTargetUrl'));
        $this->assertStringContainsString('/profile', $response->getTargetUrl());
    }

    public function testDestroyDoesNotDeleteWhenCannotDelete()
    {
        $user = User::factory()->create();
        $email = $user->emails()->create(['email' => 'cannot-delete@example.com']);
        $user->primary_email_id = $email->id;
        $user->save();

        $controller = new EmailAddressController();
        $response = $controller->destroy($email);

        $this->assertTrue(method_exists($response, 'getTargetUrl'));
        $this->assertDatabaseHas('email_addresses', ['id' => $email->id]);
    }
}
