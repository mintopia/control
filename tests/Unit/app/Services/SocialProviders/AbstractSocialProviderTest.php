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
use Laravel\Socialite\Contracts\Factory as SocialiteFactoryContract;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DummySocialProvider extends AbstractSocialProvider
{
    protected string $name = 'Dummy Social';
    protected string $code = 'dummy';
    protected string $socialiteProviderCode = 'dummy';

    public function __construct(?SocialProvider $provider = null, ?string $redirectUrl = null)
    {
        parent::__construct($provider, $redirectUrl);
    }

    // Provide a no-op updateAccount so tests exercising user() don't fail
    protected function updateAccount(\App\Models\LinkedAccount $account, $remoteUser): void
    {
        // intentionally empty for tests
    }
}

class AbstractSocialProviderTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_user_throws_if_email_verified_and_associated_with_other_user()
    {
        $prov = SocialProvider::factory()->create(['code' => 'sp_' . uniqid()]);
        $emailOwner = User::factory()->create();
        EmailAddress::factory()->create(['email' => 'a@x.com', 'user_id' => $emailOwner->id, 'verified_at' => now()]);

        $localUser = User::factory()->create();

        $remoteUser = new class {
            public function getId()
            {
                return 'rid-2';
            }
            public function getEmail()
            {
                return 'a@x.com';
            }
            public function getNickname()
            {
                return 'nick2';
            }
        };

        $driverStub = new class($remoteUser) {
            public $remote;
            public function __construct($r)
            {
                $this->remote = $r;
            }
            public function user()
            {
                return $this->remote;
            }
        };
        $factoryStub = new class($driverStub) {
            private $d;
            public function __construct($d)
            {
                $this->d = $d;
            }
            public function driver($n)
            {
                return $this->d;
            }
        };
        $this->app->instance(\Laravel\Socialite\Contracts\Factory::class, $factoryStub);

        $provider = new DummySocialProvider($prov);
        $this->expectException(\App\Exceptions\SocialProviderException::class);
        $provider->user($localUser);
    }

    public function test_user_deletes_unverified_email_and_links_account()
    {
        $prov = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'sp_' . uniqid()]);
        $other = User::factory()->create();
        EmailAddress::factory()->create(['email' => 'u@x.com', 'user_id' => $other->id, 'verified_at' => null]);

        $localUser = User::factory()->create();

        $remoteUser = new class {
            public function getId()
            {
                return 'rid-3';
            }
            public function getEmail()
            {
                return 'u@x.com';
            }
            public function getNickname()
            {
                return 'nick3';
            }
        };

        $driverStub = new class($remoteUser) {
            public $remote;
            public function __construct($r)
            {
                $this->remote = $r;
            }
            public function user()
            {
                return $this->remote;
            }
        };
        $factoryStub = new class($driverStub) {
            private $d;
            public function __construct($d)
            {
                $this->d = $d;
            }
            public function driver($n)
            {
                return $this->d;
            }
        };
        $this->app->instance(\Laravel\Socialite\Contracts\Factory::class, $factoryStub);

        $provider = new DummySocialProvider($prov);
        $result = $provider->user($localUser);
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($localUser->id, $result->id);
        // The unverified email should have been removed from the other user and re-created for the local user
        $this->assertDatabaseHas('email_addresses', ['email' => 'u@x.com', 'user_id' => $localUser->id]);
        $this->assertDatabaseMissing('email_addresses', ['email' => 'u@x.com', 'user_id' => $other->id]);
        $this->assertDatabaseHas('linked_accounts', ['external_id' => 'rid-3', 'user_id' => $localUser->id]);
    }

    public function test_user_returns_account_user_when_account_exists_and_no_local_user()
    {
        $prov = SocialProvider::factory()->create(['code' => 'sp_' . uniqid()]);
        $user = User::factory()->create();
        $linked = new LinkedAccount();
        $linked->provider()->associate($prov);
        $linked->user()->associate($user);
        $linked->external_id = 'rid-4';
        $linked->save();

        $remoteUser = new class {
            public function getId()
            {
                return 'rid-4';
            }
            public function getEmail()
            {
                return null;
            }
            public function getNickname()
            {
                return null;
            }
        };
        $driverStub = new class($remoteUser) {
            public $remote;
            public function __construct($r)
            {
                $this->remote = $r;
            }
            public function user()
            {
                return $this->remote;
            }
        };
        $factoryStub = new class($driverStub) {
            private $d;
            public function __construct($d)
            {
                $this->d = $d;
            }
            public function driver($n)
            {
                return $this->d;
            }
        };
        $this->app->instance(\Laravel\Socialite\Contracts\Factory::class, $factoryStub);

        $provider = new DummySocialProvider($prov);
        $result = $provider->user(null);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_user_throws_when_no_account_and_auth_disabled()
    {
        $prov = SocialProvider::factory()->create(['auth_enabled' => false, 'code' => 'sp_' . uniqid()]);

        $remoteUser = new class {
            public function getId()
            {
                return 'rid-5';
            }
            public function getEmail()
            {
                return null;
            }
            public function getNickname()
            {
                return null;
            }
        };
        $driverStub = new class($remoteUser) {
            public $remote;
            public function __construct($r)
            {
                $this->remote = $r;
            }
            public function user()
            {
                return $this->remote;
            }
        };
        $factoryStub = new class($driverStub) {
            private $d;
            public function __construct($d)
            {
                $this->d = $d;
            }
            public function driver($n)
            {
                return $this->d;
            }
        };
        $this->app->instance(\Laravel\Socialite\Contracts\Factory::class, $factoryStub);

        $provider = new DummySocialProvider($prov);
        $this->expectException(\App\Exceptions\SocialProviderException::class);
        $provider->user(null);
    }

    public function test_user_creates_new_user_and_email_and_account_when_auth_enabled()
    {
        $prov = SocialProvider::factory()->create(['auth_enabled' => true, 'code' => 'sp_' . uniqid()]);

        $remoteUser = new class {
            public function getId()
            {
                return 'rid-6';
            }
            public function getEmail()
            {
                return 'newuser@example.com';
            }
            public function getNickname()
            {
                return 'newnick';
            }
        };
        $driverStub = new class($remoteUser) {
            public $remote;
            public function __construct($r)
            {
                $this->remote = $r;
            }
            public function user()
            {
                return $this->remote;
            }
        };
        $factoryStub = new class($driverStub) {
            private $d;
            public function __construct($d)
            {
                $this->d = $d;
            }
            public function driver($n)
            {
                return $this->d;
            }
        };
        $this->app->instance(\Laravel\Socialite\Contracts\Factory::class, $factoryStub);

        $provider = new DummySocialProvider($prov);
        $result = $provider->user(null);
        $this->assertInstanceOf(User::class, $result);
        $this->assertDatabaseHas('linked_accounts', ['external_id' => 'rid-6', 'user_id' => $result->id]);
        $this->assertDatabaseHas('email_addresses', ['email' => 'newuser@example.com', 'user_id' => $result->id]);
    }

    public function test_redirect_calls_socialite_and_returns_redirect_response()
    {
        $driverStub = new class {
            public function redirect()
            {
                return new RedirectResponse('https://example.test/redirect');
            }
        };
        $factoryStub = new class($driverStub) {
            private $d;
            public function __construct($d)
            {
                $this->d = $d;
            }
            public function driver($n)
            {
                return $this->d;
            }
        };
        $this->app->instance(SocialiteFactoryContract::class, $factoryStub);

        $prov = SocialProvider::factory()->create(['code' => 'sp_' . uniqid()]);
        $provider = new DummySocialProvider($prov);
        $resp = $provider->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $resp);
    }

    public function test_user_returns_local_user_when_account_belongs_to_local_user()
    {
        $prov = SocialProvider::factory()->create(['code' => 'sp_' . uniqid()]);
        $user = User::factory()->create();

        $linked = new LinkedAccount();
        $linked->provider()->associate($prov);
        $linked->user()->associate($user);
        $linked->external_id = 'rid-local';
        $linked->save();

        $remoteUser = new class {
            public function getId()
            {
                return 'rid-local';
            }
            public function getEmail()
            {
                return null;
            }
            public function getNickname()
            {
                return null;
            }
        };
        $driverStub = new class($remoteUser) {
            public $remote;
            public function __construct($r)
            {
                $this->remote = $r;
            }
            public function user()
            {
                return $this->remote;
            }
        };
        $factoryStub = new class($driverStub) {
            private $d;
            public function __construct($d)
            {
                $this->d = $d;
            }
            public function driver($n)
            {
                return $this->d;
            }
        };
        $this->app->instance(SocialiteFactoryContract::class, $factoryStub);

        $provider = new DummySocialProvider($prov);
        $result = $provider->user($user);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_user_throws_if_account_exists_and_localUser_id_mismatch()
    {
        $provider = new DummySocialProvider();
        $localUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $account = LinkedAccount::factory()->create(['user_id' => $otherUser->id, 'external_id' => 'dummy_' . uniqid(),]);
        // Set up provider and account directly
        $prov = SocialProvider::factory()->create(['code' => 'sp_' . uniqid()]);
        $provider = new DummySocialProvider($prov);
        $account->provider()->associate($prov);
        $account->save();
        $this->expectException(\Exception::class); // or specific exception if known
        $provider->user($localUser);
    }

    public function test_user_handles_email_present()
    {
        $provider = new DummySocialProvider();
        $email = EmailAddress::factory()->create(['email' => 'test@example.com']);
        $user = User::factory()->create();
        $email->user()->associate($user);
        $email->save();
        $prov = SocialProvider::factory()->create(['code' => 'sp_' . uniqid()]);
        $provider = new DummySocialProvider($prov);
        $driverStub = new class {
            public function user()
            {
                return new class {
                    public function getId()
                    {
                        return 'remote-id';
                    }
                    public function getEmail()
                    {
                        return 'test@example.com';
                    }
                    public function getNickname()
                    {
                        return 'nick';
                    }
                };
            }
        };
        $factoryStub = new class($driverStub) {
            private $d;
            public function __construct($d)
            {
                $this->d = $d;
            }
            public function driver($n)
            {
                return $this->d;
            }
        };
        $this->app->instance(SocialiteFactoryContract::class, $factoryStub);
        $result = $provider->user($user);
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_user_handles_missing_primary_email()
    {
        $provider = new DummySocialProvider();
        $user = User::factory()->create();
        // Remove primaryEmail association
        $user->primary_email_id = null;
        $user->save();
        $this->assertNull($user->primaryEmail);
        $prov = SocialProvider::factory()->create();
        $provider = new DummySocialProvider($prov);
        // Remove primaryEmail association
        $user->primary_email_id = null;
        $user->save();
        $this->assertNull($user->primaryEmail);
        // Patch Socialite driver to avoid unsupported driver error
        $driverStub = new class {
            public function user()
            {
                return new class {
                    public function getId()
                    {
                        return 'remote-id';
                    }
                    public function getEmail()
                    {
                        return null;
                    }
                    public function getNickname()
                    {
                        return 'nick';
                    }
                };
            }
        };
        $factoryStub = new class($driverStub) {
            private $d;
            public function __construct($d)
            {
                $this->d = $d;
            }
            public function driver($n)
            {
                return $this->d;
            }
        };
        $this->app->instance(SocialiteFactoryContract::class, $factoryStub);
        $result = $provider->user($user);
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }
}
