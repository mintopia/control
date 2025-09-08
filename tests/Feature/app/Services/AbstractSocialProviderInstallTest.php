<?php

namespace Tests\Feature\app\Services;

use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbstractSocialProviderInstallTest extends TestCase
{
    use RefreshDatabase;

    protected $providerSvc;

    protected function setUp(): void
    {
        parent::setUp();
        // Inline dummy provider setup
        $this->providerSvc = new class extends \App\Services\SocialProviders\AbstractSocialProvider {
            protected string $name = 'Install Dummy';
            protected string $code = 'install_dummy_test_fixed';
            protected string $socialiteProviderCode = 'install_dummy_code';
            protected function updateAccount(\App\Models\LinkedAccount $account, $remoteUser): void
            {
            }
        };
    }

    public function testInstallCreatesProviderAndSettings()
    {
        $installed = $this->providerSvc->install();

        $this->assertInstanceOf(SocialProvider::class, $installed);
        $this->assertDatabaseHas('social_providers', ['code' => $installed->code]);

        // Settings from configMapping() should be created
        $this->assertDatabaseHas('provider_settings', ['provider_id' => $installed->id, 'code' => 'client_id']);
        $this->assertDatabaseHas('provider_settings', ['provider_id' => $installed->id, 'code' => 'client_secret']);

        // client_secret should be marked encrypted (boolean true stored)
        $secret = ProviderSetting::whereProviderId($installed->id)->whereCode('client_secret')->first();
        $this->assertNotNull($secret);
        $this->assertTrue((bool)$secret->encrypted);
    }

    public function testInstallIdempotentOnExistingProvider()
    {
        $svc = $this->providerSvc;
        $code = 'install_dummy_test_fixed';

        // Create an existing provider
        $existing = SocialProvider::factory()->create(['code' => $code]);

        // Calling install should return the existing provider instance
        $installed = $svc->install();
        $this->assertEquals($existing->id, $installed->id);

        // Ensure at least one setting row exists for the provider
        $this->assertDatabaseHas('provider_settings', ['provider_id' => $existing->id, 'code' => 'client_id']);
    }
}
