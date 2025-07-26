<?php

namespace Tests\App;

use App\Casts\SettingValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\TestCase;

class SettingValueTest extends TestCase
{
    public function testGetDecryptsEncryptedValue()
    {
        $model = $this->createMock(Model::class);
        $cast = new SettingValue();

        $encryptedValue = Crypt::encrypt('test-value');
        $result = $cast->get($model, 'value', $encryptedValue, ['encrypted' => true]);

        $this->assertEquals('test-value', $result);
    }

    public function testGetReturnsPlainValueIfNotEncrypted()
    {
        $model = $this->createMock(Model::class);
        $cast = new SettingValue();

        $result = $cast->get($model, 'value', 'plain-value', ['encrypted' => false]);

        $this->assertEquals('plain-value', $result);
    }

    public function testSetEncryptsValueIfRequired()
    {
        $model = $this->createMock(Model::class);
        $cast = new SettingValue();

        $result = $cast->set($model, 'value', 'test-value', ['encrypted' => true]);

        $this->assertNotEquals('test-value', $result);
        $this->assertEquals('test-value', Crypt::decrypt($result));
    }

    public function testSetReturnsPlainValueIfNotEncrypted()
    {
        $model = $this->createMock(Model::class);
        $cast = new SettingValue();

        $result = $cast->set($model, 'value', 'plain-value', ['encrypted' => false]);

        $this->assertEquals('plain-value', $result);
    }
}
