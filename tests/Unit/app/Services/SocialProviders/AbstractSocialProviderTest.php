<?php

namespace Tests\Unit\app\Services\SocialProviders;

use Tests\TestCase;
use App\Services\SocialProviders\AbstractSocialProvider;
use App\Models\SocialProvider;
use App\Models\ProviderSetting;
use App\Models\User;
use App\Models\LinkedAccount;
use App\Models\EmailAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Mockery;

class DummySocialProvider extends AbstractSocialProvider
{
    protected string $name = 'Dummy Social';
    protected string $code = 'dummy';
    protected string $socialiteProviderCode = 'dummy';

    public function __construct(?SocialProvider $provider = null, ?string $redirectUrl = null)
    {
        parent::__construct($provider, $redirectUrl);
    }
}

class AbstractSocialProviderTest extends TestCase
{
    public function test_config_mapping_returns_expected_array()
    {
        $provider = new DummySocialProvider();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('client_id', $mapping);
        $this->assertEquals('Client ID', $mapping['client_id']->name);
        $this->assertArrayHasKey('client_secret', $mapping);
        $this->assertEquals('Client Secret', $mapping['client_secret']->name);
        $this->assertTrue($mapping['client_secret']->encrypted);
    }

    public function test_install_creates_social_provider_and_settings()
    {
        $provider = new DummySocialProvider();
        $socialProvider = $provider->install();

        $this->assertInstanceOf(SocialProvider::class, $socialProvider);
        $this->assertEquals('Dummy Social', $socialProvider->name);
        $this->assertEquals('dummy', $socialProvider->code);

        $settings = $socialProvider->settings()->pluck('code')->toArray();
        $this->assertContains('client_id', $settings);
        $this->assertContains('client_secret', $settings);
    }

    public function test_install_does_not_duplicate_provider()
    {
        $provider = new DummySocialProvider();
        $first = $provider->install();
        $second = $provider->install();

        $this->assertEquals($first->id, $second->id);
        $this->assertCount(1, SocialProvider::whereCode('dummy')->get());
    }

    public function test_install_settings_updates_existing_settings()
    {
        $provider = new DummySocialProvider();
        $socialProvider = SocialProvider::factory()->create([
            'name' => 'Dummy Social',
            'code' => 'dummy',
            'provider_class' => DummySocialProvider::class,
        ]);
        $providerSetting = ProviderSetting::factory()->create([
            'provider_id' => $socialProvider->id,
            'code' => 'client_id',
            'name' => 'Old Name',
        ]);
        $provider = new DummySocialProvider($socialProvider);
        $provider->installSettings();

        $providerSetting->refresh();
        $this->assertEquals('Client ID', $providerSetting->name);
    }

    public function test_redirect_returns_redirect_response()
    {
        $provider = new DummySocialProvider();
        $mockSocialite = Mockery::mock();
        $mockSocialite->shouldReceive('redirect')->once()->andReturn(new RedirectResponse('/dummy-redirect'));
        Socialite::shouldReceive('driver')->with('dummy')->andReturn($mockSocialite);

        $response = $provider->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/dummy-redirect', $response->getTargetUrl());
    }
}
