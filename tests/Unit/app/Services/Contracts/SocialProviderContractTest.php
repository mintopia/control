<?php

namespace Tests\Unit\app\Services\Contracts;

use App\Models\SocialProvider;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;

class SocialProviderContractTest extends TestCase
{
    public function testConfigMappingReturnsExpectedArray()
    {
        $provider = new HelperClasses\DummySocialProvider();
        $mapping = $provider->configMapping();
        $this->assertArrayHasKey('client_id', $mapping);
        $this->assertEquals('Client ID', $mapping['client_id']['name']);
        $this->assertEquals('dummy-client-id', $mapping['client_id']['value']);
    }

    public function testInstallReturnsSocialProviderInstance()
    {
        $provider = new HelperClasses\DummySocialProvider();
        $socialProvider = $provider->install();
        $this->assertInstanceOf(SocialProvider::class, $socialProvider);
        $this->assertEquals('Dummy', $socialProvider->name);
        $this->assertEquals('dummy', $socialProvider->code);
    }

    public function testRedirectReturnsRedirectResponse()
    {
        $provider = new HelperClasses\DummySocialProvider();
        $response = $provider->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/dummy-redirect', $response->getTargetUrl());
    }

    public function testUserReturnsUserInstance()
    {
        $provider = new HelperClasses\DummySocialProvider();
        $user = $provider->user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Dummy User', $user->name);

        $existingUser = new User(['name' => 'Existing']);
        $user2 = $provider->user($existingUser);
        $this->assertSame($existingUser, $user2);
    }
}
