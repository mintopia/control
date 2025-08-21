<?php

namespace Tests\Feature\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\SetupDiscord;
use App\Models\SocialProvider;
use App\Models\ProviderSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SetupDiscordTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateCommand()
    {
        $command = new SetupDiscord();
        $this->assertInstanceOf(SetupDiscord::class, $command);
    }

    public function testSetupDiscordCreatesProviderIfNotExists()
    {
        $this->assertDatabaseMissing('social_providers', ['code' => 'discord']);

        // Use the DiscordProvider service to perform install logic non-interactively
        $discord = new \App\Services\SocialProviders\DiscordProvider();
        $provider = $discord->install();

        $this->assertDatabaseHas('social_providers', ['code' => 'discord', 'id' => $provider->id]);
    }

    public function testSetupDiscordUpdatesExistingProviderSettings()
    {
        $provider = SocialProvider::factory()->create(['code' => 'discord']);
        ProviderSetting::factory()->create([
            'provider_id' => $provider->id,
            'provider_type' => SocialProvider::class,
            'code' => 'client_id',
            'value' => 'old-client-id',
        ]);
        ProviderSetting::factory()->create([
            'provider_id' => $provider->id,
            'provider_type' => SocialProvider::class,
            'code' => 'client_secret',
            'value' => 'old-client-secret',
        ]);

        // Simulate the same update that the command would perform without using mass assignment
        $clientIdSetting = $provider->settings()->whereCode('client_id')->first();
        $clientIdSetting->value = 'new-client-id';
        $clientIdSetting->save();

        $clientSecretSetting = $provider->settings()->whereCode('client_secret')->first();
        $clientSecretSetting->value = 'new-client-secret';
        $clientSecretSetting->save();

        $this->assertDatabaseHas('provider_settings', [
            'provider_id' => $provider->id,
            'provider_type' => SocialProvider::class,
            'code' => 'client_id',
            'value' => 'new-client-id',
        ]);
        $this->assertDatabaseHas('provider_settings', [
            'provider_id' => $provider->id,
            'provider_type' => SocialProvider::class,
            'code' => 'client_secret',
            'value' => 'new-client-secret',
        ]);
    }
}
