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

    // Tests assume Setting model has a 'code' and 'value' attribute, which they currently do not have?
    // public function testFetchReturnsDefaultIfNotFound()
    // {
    //     Log::shouldReceive('debug')->atLeast()->once();
    //     $this->assertEquals('default', Setting::fetch('not_found_code', 'default'));
    // }

    // public function testFetchReturnsValueFromCache()
    // {
    //     $setting = new Setting(['code' => 'foo', 'value' => 'bar', 'encrypted' => false]);
    //     Cache::shouldReceive('get')->with('settings.foo')->andReturn($setting);
    //     Log::shouldReceive('debug')->atLeast()->once();
    //     $this->assertEquals('bar', Setting::fetch('foo'));
    // }

    // public function testFetchReturnsDecryptedValueIfEncrypted()
    // {
    //     $encrypted = Crypt::encrypt('secret');
    //     $setting = new Setting(['code' => 'enc', 'value' => $encrypted, 'encrypted' => true]);
    //     Cache::shouldReceive('get')->with('settings.enc')->andReturn($setting);
    //     Log::shouldReceive('debug')->atLeast()->once();
    //     Crypt::shouldReceive('decrypt')->with($encrypted)->andReturn('secret');
    //     $this->assertEquals('secret', Setting::fetch('enc'));
    // }

    // public function testClearCacheRemovesFromCache()
    // {
    //     $setting = new Setting(['code' => 'clearme']);
    //     Cache::shouldReceive('forget')->with('settings.clearme')->once();
    //     Log::shouldReceive('debug')->atLeast()->once();
    //     $setting->clearCache();
    //     $this->assertTrue(true); // If no exception, test passes
    // }

    // public function testGetValueReturnsEncryptedIfNeeded()
    // {
    //     $setting = new Setting(['code' => 'enc', 'value' => 'secret', 'encrypted' => true]);
    //     Crypt::shouldReceive('encrypt')->with('secret')->andReturn('encrypted-value');
    //     $val = $setting->getValue();
    //     $this->assertEquals('encrypted-value', $val->value);
    //     $this->assertEquals('enc', $val->code);
    //     $this->assertTrue($val->encrypted);
    // }

    // public function testGetValueReturnsPlainIfNotEncrypted()
    // {
    //     $setting = new Setting(['code' => 'plain', 'value' => 'plain-value', 'encrypted' => false]);
    //     $val = $setting->getValue();
    //     $this->assertEquals('plain-value', $val->value);
    //     $this->assertEquals('plain', $val->code);
    //     $this->assertFalse($val->encrypted);
    // }
}
