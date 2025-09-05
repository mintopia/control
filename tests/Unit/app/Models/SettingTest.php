<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SettingTest extends TestCase
{
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
        \Illuminate\Support\Facades\Cache::shouldReceive('get')->with("settings.{$code}")->andReturn($cached);
        \Illuminate\Support\Facades\Log::shouldReceive('debug')->atLeast()->once();

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

        \Illuminate\Support\Facades\Cache::shouldReceive('get')->with("settings.{$code}")->andReturn($cached);
        \Illuminate\Support\Facades\Log::shouldReceive('debug')->atLeast()->once();
        \Illuminate\Support\Facades\Crypt::shouldReceive('decrypt')->andReturn($decrypted);

        $this->assertEquals($decrypted, Setting::fetch($code));
    }

    public function testToStringNameReturnsCode()
    {
        $s = new Setting(['code' => 'my_code']);
        $ref = new \ReflectionClass($s);
        $m = $ref->getMethod('toStringName');
        $m->setAccessible(true);
        $this->assertEquals('my_code', $m->invoke($s));
    }
    // Additional DB-backed or duplicated tests removed to avoid conflicts with cache/db in unit tests.
}
