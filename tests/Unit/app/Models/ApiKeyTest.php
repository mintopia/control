<?php

namespace Tests\Unit\app\Models;

use App\Models\ApiKey;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_implements_authenticatable_contract()
    {
        $key = new ApiKey;
        $this->assertInstanceOf(Authenticatable::class, $key);
    }

    public function test_can_be_persisted_with_required_fields()
    {
        $key = ApiKey::factory()->create([
            'name' => 'Test integration',
        ]);

        $this->assertDatabaseHas('api_keys', [
            'id' => $key->id,
            'name' => 'Test integration',
            'enabled' => true,
        ]);
        $this->assertNotEmpty($key->key_hash);
        $this->assertEquals(4, strlen($key->last_four));
    }

    public function test_enabled_is_cast_to_boolean()
    {
        $key = ApiKey::factory()->create(['enabled' => 1]);
        $this->assertTrue($key->enabled);
        $key->enabled = 0;
        $key->save();
        $this->assertFalse($key->fresh()->enabled);
    }

    public function test_last_used_at_is_cast_to_carbon()
    {
        $key = ApiKey::factory()->create(['last_used_at' => now()]);
        $this->assertInstanceOf(Carbon::class, $key->fresh()->last_used_at);
    }

    public function test_generate_returns_prefixed_key_and_persists_hash()
    {
        [$apiKey, $plaintext] = ApiKey::generate('Integration A');

        $this->assertStringStartsWith('ctrl_', $plaintext);
        $this->assertEquals(45, strlen($plaintext)); // ctrl_ (5) + 40 hex chars
        $this->assertEquals(hash('sha256', $plaintext), $apiKey->key_hash);
        $this->assertEquals(substr($plaintext, -4), $apiKey->last_four);
        $this->assertEquals('Integration A', $apiKey->name);
        $this->assertTrue($apiKey->enabled);
    }

    public function test_find_by_plaintext_returns_model_and_null_for_miss()
    {
        [$apiKey, $plaintext] = ApiKey::generate('A');

        $found = ApiKey::findByPlaintext($plaintext);
        $this->assertNotNull($found);
        $this->assertEquals($apiKey->id, $found->id);

        $this->assertNull(ApiKey::findByPlaintext('ctrl_unknown'));
    }
}
