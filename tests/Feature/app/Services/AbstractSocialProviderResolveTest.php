<?php

namespace Tests\Feature\app\Services\SocialProviders;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\SocialProviders\AbstractSocialProvider;
use App\Models\SocialProvider;
use Illuminate\Support\Facades\Auth;

class ResolveDummyProvider extends AbstractSocialProvider
{
    protected string $name = 'Resolve Dummy';
    protected string $code = 'resolve_dummy_test';
    protected string $socialiteProviderCode = 'resolve_dummy_code';

    protected function updateAccount(\App\Models\LinkedAccount $account, $remoteUser): void
    {
        // no-op
    }
}

class AbstractSocialProviderResolveTest extends TestCase
{
    use RefreshDatabase;

    public function test_constructor_keeps_explicit_redirect_url()
    {
        $prov = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'rd_x']);
        $svc = new ResolveDummyProvider($prov, 'https://example.test/custom');

        $ref = new \ReflectionClass($svc);
        $p = $ref->getProperty('redirectUrl');
        $p->setAccessible(true);
        $this->assertEquals('https://example.test/custom', $p->getValue($svc));
    }

    public function test_resolve_sets_login_return_when_guest_and_auth_enabled()
    {
        Auth::shouldReceive('guest')->andReturn(true);
        $prov = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'rd_guest']);
        $svc = new ResolveDummyProvider($prov);

        $ref = new \ReflectionClass($svc);
        $p = $ref->getProperty('redirectUrl');
        $p->setAccessible(true);
        $val = $p->getValue($svc);

        $this->assertIsString($val);
        $this->assertStringContainsString('resolve_dummy_test', $val);
    }

    public function test_resolve_sets_linkedaccounts_when_not_guest()
    {
        Auth::shouldReceive('guest')->andReturn(false);
        $prov = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'rd_not_guest']);
        $svc = new ResolveDummyProvider($prov);

        $ref = new \ReflectionClass($svc);
        $p = $ref->getProperty('redirectUrl');
        $p->setAccessible(true);
        $val = $p->getValue($svc);

        $this->assertIsString($val);
        $this->assertStringContainsString('resolve_dummy_test', $val);
    }
}
