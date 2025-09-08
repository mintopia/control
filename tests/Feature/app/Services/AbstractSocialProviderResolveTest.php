<?php

namespace Tests\Feature\app\Services;

use App\Models\SocialProvider;
use App\Services\SocialProviders\AbstractSocialProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use ReflectionClass;
use Tests\TestCase;

class AbstractSocialProviderResolveTest extends TestCase
{
    use RefreshDatabase;

    protected \Closure $makeResolveProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeResolveProvider = function (\App\Models\SocialProvider $prov, ?string $redirectUrl = null) {
            return new class ($prov, $redirectUrl) extends AbstractSocialProvider {
                protected string $name = 'Resolve Dummy';
                protected string $code = 'resolve_dummy_test';
                protected string $socialiteProviderCode = 'resolve_dummy_code';

                protected function updateAccount(\App\Models\LinkedAccount $account, $remoteUser): void
                {
                    // no-op
                }
            };
        };
    }

    protected function makeResolveProvider(\App\Models\SocialProvider $prov, ?string $redirectUrl = null)
    {
        $factory = $this->makeResolveProvider;
        if (!is_callable($factory)) {
            throw new \RuntimeException('makeResolveProvider closure not initialized');
        }

        return $factory($prov, $redirectUrl);
    }

    public function testConstructorKeepsExplicitRedirectUrl()
    {
        $prov = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'rd_x']);
        $svc = $this->makeResolveProvider($prov, 'https://example.test/custom');

        $ref = new ReflectionClass($svc);
        $p = $ref->getProperty('redirectUrl');
        $p->setAccessible(true);
        $this->assertEquals('https://example.test/custom', $p->getValue($svc));
    }

    public function testResolveSetsLoginReturnWhenGuestAndAuthEnabled()
    {
        Auth::shouldReceive('guest')->andReturn(true);
        $prov = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'rd_guest']);
        $svc = $this->makeResolveProvider($prov);

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
        $svc = $this->makeResolveProvider($prov);

        $ref = new ReflectionClass($svc);
        $p = $ref->getProperty('redirectUrl');
        $p->setAccessible(true);
        $val = $p->getValue($svc);

        $this->assertIsString($val);
        $this->assertStringContainsString('resolve_dummy_test', $val);
    }
}
