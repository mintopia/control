<?php

namespace Tests\Feature\app\Services;

use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use Tests\Feature\app\Services\HelperClasses\InstallDummyProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbstractSocialProviderInstallTest extends TestCase
{
    use RefreshDatabase;

    public function testInstallCreatesProviderAndSettings()
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

    public function testInstallIdempotentOnExistingProvider()
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
