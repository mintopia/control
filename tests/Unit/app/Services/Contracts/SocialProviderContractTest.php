<?php

namespace Tests\Unit\app\Services\Contracts;

use Tests\TestCase;
use App\Services\Contracts\SocialProviderContract;
use App\Models\SocialProvider;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class DummySocialProvider implements SocialProviderContract
{
    public function __construct(?SocialProvider $provider = null, ?string $redirectUrl = null) {}

    public function configMapping(): array
    {
        return [
            'client_id' => [
                'name' => 'Client ID',
                'validation' => 'required|string',
                'value' => 'dummy-client-id',
            ],
        ];
    }

    public function install(): SocialProvider
    {
        return new SocialProvider(['name' => 'Dummy', 'code' => 'dummy']);
    }

    public function redirect(): RedirectResponse
    {
        return new RedirectResponse('/dummy-redirect');
    }

    public function user(?User $localUser = null)
    {
        return $localUser ?: new User(['name' => 'Dummy User']);
    }
}

class SocialProviderContractTest extends TestCase
{
    public function test_config_mapping_returns_expected_array()
    {
        $provider = new DummySocialProvider();
        $mapping = $provider->configMapping();
        $this->assertArrayHasKey('client_id', $mapping);
        $this->assertEquals('Client ID', $mapping['client_id']['name']);
        $this->assertEquals('dummy-client-id', $mapping['client_id']['value']);
    }

    public function test_install_returns_social_provider_instance()
    {
        $provider = new DummySocialProvider();
        $socialProvider = $provider->install();
        $this->assertInstanceOf(SocialProvider::class, $socialProvider);
        $this->assertEquals('Dummy', $socialProvider->name);
        $this->assertEquals('dummy', $socialProvider->code);
    }

    public function test_redirect_returns_redirect_response()
    {
        $provider = new DummySocialProvider();
        $response = $provider->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/dummy-redirect', $response->getTargetUrl());
    }

    public function test_user_returns_user_instance()
    {
        $provider = new DummySocialProvider();
        $user = $provider->user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Dummy User', $user->name);

        $existingUser = new User(['name' => 'Existing']);
        $user2 = $provider->user($existingUser);
        $this->assertSame($existingUser, $user2);
    }
}
