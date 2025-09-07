<?php

namespace Tests\Unit\app\Models;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateSetting()
    {
        $setting = new Setting();
        $this->assertInstanceOf(Setting::class, $setting);
    }

    public function testFetchReturnsDefaultWhenCachedSettingHasNullValue()
    {
        $code = 'foo_null';
        $default = 'def';
        $cached = new Setting(['code' => $code]);
        $cached->value = null;
        // Have Cache return our in-memory Setting object
        Cache::shouldReceive('get')->with("settings.{$code}")->andReturn($cached);
        Log::shouldReceive('debug')->atLeast()->once();

        $this->assertEquals($default, Setting::fetch($code, $default));
    }

    public function testFetchDecryptsCachedEncryptedValue()
    {
        $code = 'enc_setting';
        $encrypted = 'encblob';
        $decrypted = 'secret';
        $cached = new Setting(['code' => $code]);
        $cached->value = $encrypted;
        $cached->encrypted = 1;

        Cache::shouldReceive('get')->with("settings.{$code}")->andReturn($cached);
        Log::shouldReceive('debug')->atLeast()->once();
        Crypt::shouldReceive('decrypt')->andReturn($decrypted);

        $this->assertEquals($decrypted, Setting::fetch($code));
    }

    public function testFetchReturnsCachedUnencryptedValue()
    {
        $code = 'cached_unencrypted';
        $cached = new Setting(['code' => $code]);
        $cached->value = 'plain_value';
        $cached->encrypted = 0;

        Cache::shouldReceive('get')->with("settings.{$code}")->andReturn($cached);
        Log::shouldReceive('debug')->atLeast()->once();
        // ensure no decrypt is attempted
        Crypt::shouldReceive('decrypt')->never();

        $this->assertEquals('plain_value', Setting::fetch($code, 'default'));
    }

    public function testToStringNameReturnsCode()
    {
        $s = new Setting(['code' => 'my_code']);
        $ref = new ReflectionClass($s);
        $m = $ref->getMethod('toStringName');
        $m->setAccessible(true);
        $this->assertEquals('my_code', $m->invoke($s));
    }

    public function testFetchReadsFromDatabaseAndCachesValue()
    {
        $code = 'db_setting';
        $default = 'def';

        // Create a DB-backed setting (not encrypted) so fetch() will read from DB
        Setting::factory()->create(['code' => $code, 'value' => 'dbval', 'encrypted' => 0]);

        // First fetch should read from DB and return the stored value
        $this->assertEquals('dbval', Setting::fetch($code, $default));

        // Second fetch should hit the in-memory cache (static::$cached) and return same
        $this->assertEquals('dbval', Setting::fetch($code, $default));
    }

    public function testFetchReturnsDefaultWhenDbSettingMissing()
    {
        $code = 'missing_setting';
        $default = 'fallback';
        // Ensure no Setting exists for this code
        $this->assertNull(Setting::whereCode($code)->first());
        $result = Setting::fetch($code, $default);
        $this->assertEquals($default, $result);
        // static::$cached should be set to null for this code
        $ref = new ReflectionClass(Setting::class);
        $prop = $ref->getProperty('cached');
        $prop->setAccessible(true);
        $cached = $prop->getValue();
        $this->assertArrayHasKey($code, $cached);
        $this->assertNull($cached[$code]);
    }

    public function testFetchCachesNullAndCallsCachePutWhenDbMissingAndCacheEmpty()
    {
        $code = 'missing2';
        $default = 'fallback2';
        $key = "settings.{$code}";

        // Make Cache.get return null so code checks DB
        Cache::shouldReceive('get')->with($key)->andReturn(null);
        // Expect Cache::put called with null value when DB record missing
        Cache::shouldReceive('put')->with($key, null)->once();

        $this->assertNull(Setting::whereCode($code)->first());
        $result = Setting::fetch($code, $default);
        $this->assertEquals($default, $result);

        // static::$cached should have the code set to null
        $ref = new ReflectionClass(Setting::class);
        $prop = $ref->getProperty('cached');
        $prop->setAccessible(true);
        $cached = $prop->getValue();
        $this->assertArrayHasKey($code, $cached);
        $this->assertNull($cached[$code]);
    }
}
