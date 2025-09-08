<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Enums\SettingType;
use App\Models\ProviderSetting;
use App\Models\TicketProvider;
use App\Services\TicketProviders\AbstractTicketProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class AbstractTicketProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProviderInstance($providerModel = null, array $overrides = [])
    {
        $class = new class ($providerModel) extends AbstractTicketProvider {
            protected string $name = 'Dummy Provider';
            protected string $code = 'dummy';

            public function __construct($provider = null)
            {
                parent::__construct($provider);
            }
        };

        // Apply overrides via reflection if provided
        if (!empty($overrides)) {
            $rc = new ReflectionClass($class);
            foreach ($overrides as $prop => $value) {
                if ($rc->hasProperty($prop)) {
                    $p = $rc->getProperty($prop);
                    $p->setAccessible(true);
                    $p->setValue($class, $value);
                }
            }
        }

        return $class;
    }

    public function testConfigMappingReturnsExpectedArray()
    {
        $provider = $this->makeProviderInstance();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('apikey', $mapping);
        $this->assertEquals('API Key', $mapping['apikey']->name);
        $this->assertArrayHasKey('webhook_secret', $mapping);
        $this->assertEquals('Webhook Secret', $mapping['webhook_secret']->name);
    }

    public function testInstallCreatesTicketProviderAndSettings()
    {
        $provider = $this->makeProviderInstance();
        $ticketProvider = $provider->install();

        $this->assertInstanceOf(TicketProvider::class, $ticketProvider);
        $this->assertEquals('Dummy Provider', $ticketProvider->name);
        $this->assertEquals('dummy', $ticketProvider->code);

        $settings = $ticketProvider->settings()->pluck('code')->toArray();
        $this->assertContains('apikey', $settings);
        $this->assertContains('webhook_secret', $settings);
    }

    public function testInstallDoesNotDuplicateProvider()
    {
        $provider = $this->makeProviderInstance();
        $first = $provider->install();
        $second = $provider->install();

        $this->assertEquals($first->id, $second->id);
        $this->assertCount(1, TicketProvider::whereCode('dummy')->get());
    }

    public function testInstallSettingsUpdatesExistingSettings()
    {
        $provider = $this->makeProviderInstance();
        $ticketProvider = TicketProvider::factory()->create([
            'name' => 'Dummy Provider',
            'code' => 'dummy',
            'provider_class' => AbstractTicketProvider::class,
        ]);
        $providerSetting = ProviderSetting::factory()->create([
            'provider_type' => TicketProvider::class,
            'provider_id' => $ticketProvider->id,
            'code' => 'apikey',
            'name' => 'Old Name',
        ]);
        // Create provider instance bound to the created model
        $provider = $this->makeProviderInstance($ticketProvider);
        $provider->installSettings();

        $providerSetting->refresh();
        $this->assertEquals('API Key', $providerSetting->name);
    }

    public function testProcessWebhookReturnsTrue()
    {
        $provider = $this->makeProviderInstance();
        $request = Request::create('/webhook', 'POST');
        $this->assertTrue($provider->processWebhook($request));
    }

    public function testGetEventsReturnsEmptyArray()
    {
        $provider = $this->makeProviderInstance();
        $this->assertEquals([], $provider->getEvents());
    }

    public function testGetTicketTypesReturnsEmptyArray()
    {
        $provider = $this->makeProviderInstance();
        $this->assertEquals([], $provider->getTicketTypes('event-id'));
    }

    public function testSyncTicketsDoesNothing()
    {
        $provider = $this->makeProviderInstance();
        $this->assertNull($provider->syncTickets('test@example.com'));
    }

    public function testSyncAllTicketsDoesNothing()
    {
        $provider = $this->makeProviderInstance();
        $this->assertNull($provider->syncAllTickets(null));
    }

    public function testInstallSettingsSetsInitialValue()
    {
        // Create an anonymous subclass that provides a default value in the config mapping
        $provider = new class extends AbstractTicketProvider {
            protected string $name = 'Dummy Provider';
            protected string $code = 'dummy';

            public function configMapping(): array
            {
                return [
                    'apikey' => (object)[
                        'name' => 'API Key',
                        'validation' => 'required|string',
                        'value' => 'INIT_KEY',
                    ],
                    'webhook_secret' => (object)[
                        'name' => 'Webhook Secret',
                        'validation' => 'string',
                    ],
                ];
            }
        };

        $ticketProvider = $provider->install();
        $this->assertInstanceOf(TicketProvider::class, $ticketProvider);
        $setting = $ticketProvider->settings()->whereCode('apikey')->first();
        $this->assertEquals('INIT_KEY', $setting->value);
    }

    public function testInstallSettingsDoesNotSaveWhenNoChanges()
    {
        // Create provider model and a setting that already matches the config mapping
        $ticketProvider = TicketProvider::factory()->create([
            'name' => 'Dummy Provider',
            'code' => 'dummy',
            'provider_class' => AbstractTicketProvider::class,
        ]);
        $providerSetting = ProviderSetting::factory()->create([
            'provider_type' => TicketProvider::class,
            'provider_id' => $ticketProvider->id,
            'code' => 'apikey',
            'name' => 'API Key',
            'validation' => 'required|string',
            'encrypted' => false,
            'description' => null,
            'type' => SettingType::stString,
        ]);

        // Set created_at and updated_at to a fixed past time
        $past = Carbon::now()->subDay();
        $providerSetting->created_at = $past;
        $providerSetting->updated_at = $past;
        $providerSetting->save();

        $provider = $this->makeProviderInstance($ticketProvider);
        $provider->installSettings();

        $providerSetting->refresh();
        // Ensure no duplicate setting was created
        $this->assertCount(1, ProviderSetting::where('provider_id', $ticketProvider->id)->where('code', 'apikey')->get());
        $this->assertEquals('API Key', $providerSetting->name);
    }
}
