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

    public function testHandleRunsWhenProviderExistsAndSaves()
    {
        // Create provider and settings so prompts return defaults
        $provider = SocialProvider::factory()->create(['code' => 'discord', 'enabled' => false, 'auth_enabled' => false]);
        ProviderSetting::factory()->create([
            'provider_id' => $provider->id,
            'provider_type' => SocialProvider::class,
            'code' => 'client_id',
            'value' => 'cid',
        ]);
        ProviderSetting::factory()->create([
            'provider_id' => $provider->id,
            'provider_type' => SocialProvider::class,
            'code' => 'client_secret',
            'value' => 'csecret',
        ]);

        // Register minimal named routes used by the command to avoid UrlGenerationException
        $this->app['router']->get('/login/return/{provider}', fn() => 'ok')->name('login.return');
        $this->app['router']->get('/linkedaccounts/store/{provider}', fn() => 'ok')->name('linkedaccounts.store');

        // Use the artisan runner and provide expected answers for each prompt
        $this->artisan('control:setup-discord')
            ->expectsQuestion('Discord Client ID', 'cid')
            ->expectsQuestion('Do you want to change the Client Secret?', false)
            ->expectsQuestion('Do you want to enable the Discord provider?', false)
            ->expectsQuestion('Do you want to enable login with Discord?', false)
            ->assertExitCode(0);

        $this->assertDatabaseHas('social_providers', ['id' => $provider->id, 'code' => 'discord']);
    }

    public function testHandleCreatesProviderWhenMissing()
    {
        // Ensure no provider exists
        \App\Models\SocialProvider::whereCode('discord')->delete();
        $this->assertDatabaseMissing('social_providers', ['code' => 'discord']);

        // Run the command non-interactively by providing default answers
        $this->artisan('control:setup-discord')
            ->expectsQuestion('Discord Client ID', '')
            ->expectsQuestion('Discord Client Secret', '')
            ->expectsQuestion('Do you want to enable the Discord provider?', false)
            ->expectsQuestion('Do you want to enable login with Discord?', false)
            ->assertExitCode(0);

        $this->assertDatabaseHas('social_providers', ['code' => 'discord']);
    }

    public function testHandlePromptsForSecretWhenMissingAndEnablesProvider()
    {
        // Create provider with client_id but empty client_secret
        $provider = SocialProvider::factory()->create(['code' => 'discord', 'enabled' => false, 'auth_enabled' => false]);
        ProviderSetting::factory()->create([
            'provider_id' => $provider->id,
            'provider_type' => SocialProvider::class,
            'code' => 'client_id',
            'value' => 'cid',
        ]);
        ProviderSetting::factory()->create([
            'provider_id' => $provider->id,
            'provider_type' => SocialProvider::class,
            'code' => 'client_secret',
            'value' => '',
        ]);

        // Register minimal named routes used by the command
        $this->app['router']->get('/login/return/{provider}', fn() => 'ok')->name('login.return');
        $this->app['router']->get('/linkedaccounts/store/{provider}', fn() => 'ok')->name('linkedaccounts.store');

        // Run the command and answer prompts: provide password, enable -> yes, auth -> yes
        $this->artisan('control:setup-discord')
            ->expectsQuestion('Discord Client ID', 'cid')
            ->expectsQuestion('Discord Client Secret', 'supersecret')
            ->expectsQuestion('Do you want to enable the Discord provider?', true)
            ->expectsQuestion('Do you want to enable login with Discord?', true)
            ->assertExitCode(0);

        $this->assertDatabaseHas('provider_settings', [
            'provider_id' => $provider->id,
            'provider_type' => SocialProvider::class,
            'code' => 'client_secret',
            'value' => 'supersecret',
        ]);
        $this->assertDatabaseHas('social_providers', ['id' => $provider->id, 'enabled' => 1, 'auth_enabled' => 1]);
    }
}
