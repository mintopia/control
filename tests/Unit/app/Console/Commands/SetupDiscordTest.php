<?php

namespace Tests\Unit\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\SetupDiscord;
use App\Models\SocialProvider;
use App\Models\ProviderSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SetupDiscordTest extends TestCase
{
    use RefreshDatabase;

    // TODO Tests do not work with the current setup, need to fix
    public function testCanInstantiateCommand()
    {
        $command = new SetupDiscord();
        $this->assertInstanceOf(SetupDiscord::class, $command);
    }

    public function testSetupDiscordCreatesProviderIfNotExists()
    {
        $this->assertDatabaseMissing('social_providers', ['code' => 'discord']);
        $command = new SetupDiscord(
            fn(...$args) => 'test-client-id', // text
            fn(...$args) => 'test-client-secret', // password
            fn($label, ...$args) => match ($label) {
                'Do you want to change the Client Secret?' => false,
                'Do you want to enable the Discord provider?' => true,
                'Do you want to enable login with Discord?' => true,
                default => false,
            },
            fn(...$args) => null, // table
            fn(...$args) => null  // info
        );
        $command->handle();
        $this->assertDatabaseHas('social_providers', ['code' => 'discord']);
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
        $command = new SetupDiscord(
            fn(...$args) => 'new-client-id', // text
            fn(...$args) => 'new-client-secret', // password
            fn($label, ...$args) => match ($label) {
                'Do you want to change the Client Secret?' => true,
                'Do you want to enable the Discord provider?' => true,
                'Do you want to enable login with Discord?' => true,
                default => false,
            },
            fn(...$args) => null, // table
            fn(...$args) => null  // info
        );
        $command->handle();
        $this->assertDatabaseHas('provider_settings', [
            'provider_id' => $provider->id,
            'code' => 'client_id',
            'value' => 'new-client-id',
        ]);
        $this->assertDatabaseHas('provider_settings', [
            'provider_id' => $provider->id,
            'code' => 'client_secret',
            'value' => 'new-client-secret',
        ]);
    }
}
