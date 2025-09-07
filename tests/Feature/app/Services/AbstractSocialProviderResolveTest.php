<?php

namespace Tests\Feature\app\Services;

use App\Models\SocialProvider;
use Tests\Feature\app\Services\HelperClasses\ResolveDummyProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use ReflectionClass;
use Tests\TestCase;

class AbstractSocialProviderResolveTest extends TestCase
{
    use RefreshDatabase;

    public function testConstructorKeepsExplicitRedirectUrl()
    {
        $prov = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'rd_x']);
        $svc = new ResolveDummyProvider($prov, 'https://example.test/custom');

        $ref = new ReflectionClass($svc);
        $p = $ref->getProperty('redirectUrl');
        $p->setAccessible(true);
        $this->assertEquals('https://example.test/custom', $p->getValue($svc));
    }

    public function testResolveSetsLoginReturnWhenGuestAndAuthEnabled()
    {
        Auth::shouldReceive('guest')->andReturn(true);
        $prov = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'rd_guest']);
        $svc = new ResolveDummyProvider($prov);

        $ref = new ReflectionClass($svc);
        $p = $ref->getProperty('redirectUrl');
        $p->setAccessible(true);
        $val = $p->getValue($svc);

        $this->assertIsString($val);
        $this->assertStringContainsString('resolve_dummy_test', $val);
    }

    public function testResolveSetsLinkedaccountsWhenNotGuest()
    {
        Auth::shouldReceive('guest')->andReturn(false);
        $prov = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'rd_not_guest']);
        $svc = new ResolveDummyProvider($prov);

        $ref = new ReflectionClass($svc);
        $p = $ref->getProperty('redirectUrl');
        $p->setAccessible(true);
        $val = $p->getValue($svc);

        $this->assertIsString($val);
        $this->assertStringContainsString('resolve_dummy_test', $val);
    }
}
