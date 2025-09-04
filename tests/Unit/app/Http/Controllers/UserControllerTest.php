<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\SocialProvider;
use App\Models\User;
use App\Models\LinkedAccount;
use App\Models\Setting;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new UserController();
        $this->assertInstanceOf(UserController::class, $controller);
    }

    public function testProfileShowsAvailableProvidersExcludingLinkedAccounts()
    {
        $user = User::factory()->create();
        $providerA = SocialProvider::factory()->create(['code' => 'one']);
        $providerB = SocialProvider::factory()->create(['code' => 'two']);

        // Link providerA to the user
        LinkedAccount::factory()->create([
            'user_id' => $user->id,
            'social_provider_id' => $providerA->id,
        ]);

        $controller = new UserController();
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $controller->profile($request);
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $data = $response->getData();
        $this->assertArrayHasKey('availableLinks', $data);
        $codes = $data['availableLinks']->pluck('code')->all();
        $this->assertNotContains('one', $codes);
        $this->assertContains('two', $codes);
    }

    public function testLogoutRedirectsToHomeWithMessage()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $controller = new UserController();

        $request = \Illuminate\Http\Request::create('/', 'GET');
        // provide a session store so regenerate() works
        $request->setLaravelSession(app('session.store'));

        $response = $controller->logout($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString(route('home'), $response->getTargetUrl());
        $this->assertEquals('You have been logged out', session('successMessage'));
    }

    public function testLoginRedirectRespectsProviderEnabledFlags()
    {
        $provider = SocialProvider::factory()->create(['enabled' => false, 'auth_enabled' => false]);
        $controller = new UserController();
        $response = $controller->login_redirect($provider);
        $this->assertStringContainsString(route('login'), $response->getTargetUrl());

        // For the enabled case, avoid calling the model code path that relies on getProvider()
        // (getProvider is commented out in the model). Create a lightweight SocialProvider
        // subclass that overrides redirect() so controller->login_redirect can call it.
        $enabledProvider = new class extends \App\Models\SocialProvider {
            public function redirect(?string $redirectUrl = null)
            {
                return response('ok');
            }
        };
        $enabledProvider->enabled = true;
        $enabledProvider->auth_enabled = true;

        $resp = $controller->login_redirect($enabledProvider);
        $this->assertEquals('ok', $resp->getContent());
    }

    public function testSignupProcessAndUpdateSaveDataAndRedirect()
    {
        $user = User::factory()->create(['first_login' => true]);
        $this->actingAs($user);

        $controller = new UserController();

        // signup_process - use the real FormRequest so type hints match
        $signupRequest = new \App\Http\Requests\UserSignupRequest();
        // ensure the FormRequest has the POST data available
        $signupRequest->replace(['nickname' => 'nick', 'name' => 'Full Name']);
        $signupRequest->setUserResolver(function () use ($user) {
            return $user;
        });
        $signupRequest->setLaravelSession(app('session.store'));

        $response = $controller->signup_process($signupRequest);
        $this->assertStringContainsString(route('home'), $response->getTargetUrl());
        $this->assertEquals('nick', $user->fresh()->nickname);

        // update - use the real ProfileUpdateRequest
        $updateRequest = new \App\Http\Requests\ProfileUpdateRequest();
        // ensure the FormRequest has the POST data available
        $updateRequest->replace(['nickname' => 'newnick', 'name' => 'New Name']);
        $updateRequest->setUserResolver(function () use ($user) {
            return $user;
        });
        $updateRequest->setLaravelSession(app('session.store'));

        $response = $controller->update($updateRequest);
        $this->assertStringContainsString(route('user.profile'), $response->getTargetUrl());
        $this->assertEquals('newnick', $user->fresh()->nickname);
    }

    public function testSignupShowsView()
    {
        $user = User::factory()->create();
        // create settings used by the view
        Setting::create(['code' => 'terms', 'name' => 'Terms', 'value' => 'terms text']);
        Setting::create(['code' => 'privacypolicy', 'name' => 'Privacy', 'value' => 'privacy text']);

        $controller = new UserController();
        $request = \Illuminate\Http\Request::create('/signup', 'GET');
        $request->setUserResolver(fn() => $user);

        $resp = $controller->signup($request);
        $this->assertInstanceOf(\Illuminate\View\View::class, $resp);
        $data = $resp->getData();
        $this->assertArrayHasKey('terms', $data);
        $this->assertArrayHasKey('privacy', $data);
    }

    public function testEditShowsView()
    {
        $user = User::factory()->create();
        $controller = new UserController();
        $request = \Illuminate\Http\Request::create('/edit', 'GET');
        $request->setUserResolver(fn() => $user);

        $resp = $controller->edit($request);
        $this->assertInstanceOf(\Illuminate\View\View::class, $resp);
        $this->assertArrayHasKey('user', $resp->getData());
    }

    public function testLoginShowsProviders()
    {
        SocialProvider::factory()->create(['code' => 'one', 'enabled' => true, 'auth_enabled' => true]);
        SocialProvider::factory()->create(['code' => 'two', 'enabled' => false, 'auth_enabled' => false]);

        $controller = new UserController();
        $resp = $controller->login();
        $this->assertInstanceOf(\Illuminate\View\View::class, $resp);
        $data = $resp->getData();
        $this->assertArrayHasKey('providers', $data);
        $codes = $data['providers']->pluck('code')->all();
        $this->assertContains('one', $codes);
        $this->assertNotContains('two', $codes);
    }

    public function testLoginReturnRedirectsWhenAlreadyAuthenticated()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $provider = SocialProvider::factory()->create(['enabled' => true, 'auth_enabled' => true]);
        $controller = new UserController();

        $resp = $controller->login_return($provider);
        $this->assertStringContainsString(route('home'), $resp->getTargetUrl());
    }

    public function testLoginReturnLogsInProviderUser()
    {
        $user = User::factory()->create(['suspended' => 0]);
        $provider = new class extends \App\Models\SocialProvider {
            public function user(?string $redirectUrl = null)
            {
                return \App\Models\User::first();
            }
        };
        $provider->enabled = true;
        $provider->auth_enabled = true;

        // ensure there is a user in DB for the anonymous provider to return
        $user->save();

        $controller = new UserController();
        $resp = $controller->login_return($provider);

        $this->assertStringContainsString(route('home'), $resp->getTargetUrl());
        $this->assertTrue(\Illuminate\Support\Facades\Auth::check());
        $this->assertNotNull($user->fresh()->last_login);
    }

    public function testLoginReturnWithDisabledProviderRedirectsToLogin()
    {
        $controller = new UserController();
        $disabledProvider = new class extends \App\Models\SocialProvider {};
        $disabledProvider->enabled = false;
        $disabledProvider->auth_enabled = false;

        $resp = $controller->login_return($disabledProvider);
        $this->assertStringContainsString(route('login'), $resp->getTargetUrl());
    }

    public function testLoginReturnWithSuspendedUserRedirectsWithMessage()
    {
        $suspended = User::factory()->create(['suspended' => 1]);

        $provider = new class extends \App\Models\SocialProvider {
            public function user(?string $redirectUrl = null)
            {
                return \App\Models\User::first();
            }
        };
        $provider->enabled = true;
        $provider->auth_enabled = true;

        // ensure user exists for the provider to return
        $suspended->save();

        $controller = new UserController();
        $resp = $controller->login_return($provider);

        $this->assertStringContainsString(route('login'), $resp->getTargetUrl());
        $this->assertEquals('Your account has been suspended', $resp->getSession()->get('errorMessage'));
    }

    public function testLoginReturnHandlesSocialProviderException()
    {
        $provider = new class extends \App\Models\SocialProvider {
            public function user(?string $redirectUrl = null)
            {
                throw new \App\Exceptions\SocialProviderException('provider fail');
            }
        };
        $provider->enabled = true;
        $provider->auth_enabled = true;

        $controller = new UserController();
        $resp = $controller->login_return($provider);

        $this->assertStringContainsString(route('login'), $resp->getTargetUrl());
        $this->assertEquals('provider fail', $resp->getSession()->get('errorMessage'));
    }

    public function testLoginReturnHandlesGenericExceptionAndFallsBack()
    {
        $provider = new class extends \App\Models\SocialProvider {
            public function user(?string $redirectUrl = null)
            {
                throw new \Exception('boom');
            }
        };
        $provider->enabled = true;
        $provider->auth_enabled = true;

        $controller = new UserController();
        $resp = $controller->login_return($provider);

        $this->assertStringContainsString(route('login'), $resp->getTargetUrl());
        $this->assertEquals('Unable to login', $resp->getSession()->get('errorMessage'));
    }
}
