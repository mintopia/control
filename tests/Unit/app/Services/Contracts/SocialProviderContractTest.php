<?php

namespace Tests\Unit\app\Services\Contracts;

use App\Models\SocialProvider;
use App\Models\User;
use App\Services\Contracts\SocialProviderContract;
use Illuminate\Http\RedirectResponse;
use Tests\Traits\ProviderTestHelpers;
use ReflectionClass;
use Tests\TestCase;

class SocialProviderContractTest extends TestCase
{
    use ProviderTestHelpers;

    public function testConfigMappingReturnsExpectedArray()
    {
        $provider = $this->makeSocialProvider();
        $rc = new ReflectionClass($provider);
        $this->assertTrue($rc->implementsInterface(SocialProviderContract::class));
        $mapping = $provider->configMapping();
        $this->assertArrayHasKey('client_id', $mapping);
        $this->assertEquals('Client ID', $mapping['client_id']['name']);
        $this->assertEquals('dummy-client-id', $mapping['client_id']['value']);
    }

    public function testInstallReturnsSocialProviderInstance()
    {
        $provider = $this->makeSocialProvider();
        $socialProvider = $provider->install();
        $this->assertInstanceOf(SocialProvider::class, $socialProvider);
        $this->assertEquals('Dummy', $socialProvider->name);
        $this->assertEquals('dummy', $socialProvider->code);
    }

    public function testRedirectReturnsRedirectResponse()
    {
        $provider = $this->makeSocialProvider();
        $response = $provider->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/dummy-redirect', $response->getTargetUrl());
    }

    public function testUserReturnsUserInstance()
    {
        $provider = $this->makeSocialProvider();
        $user = $provider->user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Dummy User', $user->name);

        $existingUser = new User(['name' => 'Existing']);
        $user2 = $provider->user($existingUser);
        $this->assertSame($existingUser, $user2);
    }
}
