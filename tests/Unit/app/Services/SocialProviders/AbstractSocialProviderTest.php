<?php

namespace Tests\Unit\app\Services\SocialProviders;

use App\Exceptions\SocialProviderException;
use App\Models\EmailAddress;
use App\Models\LinkedAccount;
use App\Models\SocialProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\Factory as SocialiteFactoryContract;
use Tests\TestCase;
use Tests\Traits\ProviderTestHelpers;

class AbstractSocialProviderTest extends TestCase
{
    use RefreshDatabase;
    use ProviderTestHelpers;

    public function testConfigMappingReturnsExpectedArray()
    {
        $provider = $this->makeSocialProviderVariant();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('client_id', $mapping);
        $this->assertEquals('Client ID', $mapping['client_id']->name);
        $this->assertArrayHasKey('client_secret', $mapping);
        $this->assertEquals('Client Secret', $mapping['client_secret']->name);
        $this->assertTrue($mapping['client_secret']->encrypted);
    }


    public function testUserDeletesUnverifiedEmailAndLinksAccount()
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

        $driverStub = new class ($remoteUser) {
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
        $factoryStub = new class ($driverStub) {
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

        $provider = $this->makeSocialProviderVariant($prov);
        $result = $provider->user($localUser);
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($localUser->id, $result->id);
        // The unverified email should have been removed from the other user and re-created for the local user
        $this->assertDatabaseHas('email_addresses', ['email' => 'u@x.com', 'user_id' => $localUser->id]);
        $this->assertDatabaseMissing('email_addresses', ['email' => 'u@x.com', 'user_id' => $other->id]);
        $this->assertDatabaseHas('linked_accounts', ['external_id' => 'rid-3', 'user_id' => $localUser->id]);
    }

    public function testUserReturnsAccountUserWhenAccountExistsAndNoLocalUser()
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
        $driverStub = new class ($remoteUser) {
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
        $factoryStub = new class ($driverStub) {
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

        $provider = $this->makeSocialProviderVariant($prov);
        $result = $provider->user(null);
        $this->assertEquals($user->id, $result->id);
    }

    public function testUserCreatesNewUserAndEmailAndAccountWhenAuthEnabled()
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
        $driverStub = new class ($remoteUser) {
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
        $factoryStub = new class ($driverStub) {
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

        $provider = $this->makeSocialProviderVariant($prov);
        $result = $provider->user(null);
        $this->assertInstanceOf(User::class, $result);
        $this->assertDatabaseHas('linked_accounts', ['external_id' => 'rid-6', 'user_id' => $result->id]);
        $this->assertDatabaseHas('email_addresses', ['email' => 'newuser@example.com', 'user_id' => $result->id]);
    }

    public function testRedirectCallsSocialiteAndReturnsRedirectResponse()
    {
        $driverStub = new class {
            public function redirect()
            {
                return new RedirectResponse('https://example.test/redirect');
            }
        };
        $factoryStub = new class ($driverStub) {
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
        $provider = $this->makeSocialProviderVariant($prov);
        $resp = $provider->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $resp);
    }

    public function testUserReturnsLocalUserWhenAccountBelongsToLocalUser()
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
        $driverStub = new class ($remoteUser) {
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
        $factoryStub = new class ($driverStub) {
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

        $provider = $this->makeSocialProviderVariant($prov);
        $result = $provider->user($user);
        $this->assertEquals($user->id, $result->id);
    }

    public function testUserHandlesEmailPresent()
    {
        $provider = $this->makeSocialProviderVariant();
        $email = EmailAddress::factory()->create(['email' => 'test@example.com']);
        $user = User::factory()->create();
        $email->user()->associate($user);
        $email->save();
        $prov = SocialProvider::factory()->create(['code' => 'sp_' . uniqid()]);
        $provider = $this->makeSocialProviderVariant($prov);
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
        $factoryStub = new class ($driverStub) {
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

    public function testUserHandlesMissingPrimaryEmail()
    {
        $provider = $this->makeSocialProviderVariant();
        $user = User::factory()->create();
        // Remove primaryEmail association
        $user->primary_email_id = null;
        $user->save();
        $this->assertNull($user->primaryEmail);
        $prov = SocialProvider::factory()->create();
        $provider = $this->makeSocialProviderVariant($prov);
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
        $factoryStub = new class ($driverStub) {
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

    // Testing Exceptions
    public function testUserThrowsIfAccountExistsAndLocalUserIdMismatch()
    {
        $provider = $this->makeSocialProviderVariant();
        $localUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $account = LinkedAccount::factory()->create(['user_id' => $otherUser->id, 'external_id' => 'dummy_' . uniqid(),]);
        // Set up provider and account directly
        $prov = SocialProvider::factory()->create(['code' => 'sp_' . uniqid()]);
        $provider = $this->makeSocialProviderVariant($prov);
        $account->provider()->associate($prov);
        $account->save();
        // Patch Socialite driver so the provider->user() call doesn't fail due to unsupported driver
        $remoteUser = new class ($account->external_id) {
            private $id;

            public function __construct($id)
            {
                $this->id = $id;
            }

            public function getId()
            {
                return $this->id;
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
        $driverStub = new class ($remoteUser) {
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
        $factoryStub = new class ($driverStub) {
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

        $this->expectException(SocialProviderException::class);
        $this->expectExceptionMessage('Account is already associated with another user');
        $provider->user($localUser);
    }

    public function testUserThrowsIfEmailVerifiedAndAssociatedWithOtherUser()
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

        $driverStub = new class ($remoteUser) {
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
        $factoryStub = new class ($driverStub) {
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

        $provider = $this->makeSocialProviderVariant($prov);
        $this->expectException(SocialProviderException::class);
        $this->expectExceptionMessage('Email is already associated with another user');
        $provider->user($localUser);
    }

    public function testUserThrowsWhenNoAccountAndAuthDisabled()
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
        $driverStub = new class ($remoteUser) {
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
        $factoryStub = new class ($driverStub) {
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

        $provider = $this->makeSocialProviderVariant($prov);
        $this->expectException(SocialProviderException::class);
        $this->expectExceptionMessage('Unable to login with this account');
        $provider->user(null);
    }
}
