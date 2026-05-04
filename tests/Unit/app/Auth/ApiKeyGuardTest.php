<?php

namespace Tests\Unit\app\Auth;

use App\Auth\ApiKeyGuard;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApiKeyGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_api_key_for_valid_enabled_bearer_token()
    {
        $plaintext = '';
        $apiKey = ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$plaintext}");

        $guard = new ApiKeyGuard($request);
        $resolved = $guard->user();

        $this->assertNotNull($resolved);
        $this->assertEquals($apiKey->id, $resolved->id);
    }

    public function test_returns_null_for_unknown_token()
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', 'Bearer ctrl_unknown');

        $guard = new ApiKeyGuard($request);
        $this->assertNull($guard->user());
    }

    public function test_returns_null_for_disabled_key()
    {
        $plaintext = '';
        ApiKey::factory()->disabled()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$plaintext}");

        $guard = new ApiKeyGuard($request);
        $this->assertNull($guard->user());
    }

    public function test_returns_null_when_no_authorization_header()
    {
        $request = Request::create('/test', 'GET');
        $guard = new ApiKeyGuard($request);
        $this->assertNull($guard->user());
    }

    public function test_updates_last_used_at_on_successful_auth()
    {
        $plaintext = '';
        $apiKey = ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create(['last_used_at' => null]);

        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$plaintext}");

        $guard = new ApiKeyGuard($request);
        $guard->user();

        $this->assertNotNull($apiKey->fresh()->last_used_at);
    }

    public function test_in_memory_key_last_used_at_is_updated_after_auth()
    {
        $plaintext = '';
        ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create(['last_used_at' => null]);

        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$plaintext}");

        $guard = new ApiKeyGuard($request);
        $resolved = $guard->user();

        $this->assertNotNull($resolved);
        $this->assertNotNull($resolved->last_used_at, 'last_used_at on the returned model should be populated');
    }

    public function test_caches_resolved_key_across_multiple_calls()
    {
        $plaintext = '';
        ApiKey::factory()->withPlaintext(function ($p) use (&$plaintext) {
            $plaintext = $p;
        })->create();

        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', "Bearer {$plaintext}");

        $guard = new ApiKeyGuard($request);
        $first = $guard->user();
        $second = $guard->user();
        $this->assertSame($first, $second);
    }
}
