<?php

namespace Tests\Feature\app\Services\SocialProviders;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\SocialProviders\AbstractSocialProvider;
use App\Models\SocialProvider;
use App\Models\ProviderSetting;

class InstallDummyProvider extends AbstractSocialProvider
{
    protected string $name = 'Install Dummy';
    // Use a fixed, simple code to avoid namespace/escape mismatches in tests
    protected string $code = 'install_dummy_test_fixed';
    protected string $socialiteProviderCode = 'install_dummy_code';

    // No-op
    protected function updateAccount(\App\Models\LinkedAccount $account, $remoteUser): void {}
}

class AbstractSocialProviderInstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_creates_provider_and_settings()
    {
        $providerSvc = new InstallDummyProvider();

        $installed = $providerSvc->install();

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

    public function test_install_idempotent_on_existing_provider()
    {
        $svc = new InstallDummyProvider();
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
