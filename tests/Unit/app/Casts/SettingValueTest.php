<?php

namespace Tests\Unit\app\Casts;

use App\Casts\SettingValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SettingValueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure the app key is set for Crypt facade
        if (empty(config('app.key'))) {
            config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);
        }
    }

    public function testSetReturnsPlainValueIfNotEncrypted()
    {
        $model = $this->createMock(Model::class);
        $cast = new SettingValue();
        $model->method('getAttribute')->willReturnMap([
            ['encrypted', false],
        ]);
        $result = $cast->set($model, 'value', 'plain-value', []);
        $model->encrypted = false;
        $result = $cast->set($model, 'value', 'plain-value', []);
        $this->assertEquals('plain-value', $result);
    }

    public function testGetDecryptsEncryptedValue()
    {
        $model = new class extends Model {
            public $encrypted = true;

            public function getAttribute($key)
            {
                if ($key === 'encrypted') {
                    return $this->encrypted;
                }
                return parent::getAttribute($key);
            }
        };
        $cast = new SettingValue();
        $encryptedValue = Crypt::encrypt('test-value');
        $result = $cast->get($model, 'value', $encryptedValue, []);
        $this->assertEquals('test-value', $result);
    }

    public function testSetEncryptsValueIfRequired()
    {
        $model = new class extends Model {
            public $encrypted = true;

            public function getAttribute($key)
            {
                if ($key === 'encrypted') {
                    return $this->encrypted;
                }
                return parent::getAttribute($key);
            }
        };
        $cast = new SettingValue();
        $result = $cast->set($model, 'value', 'test-value', []);
        $this->assertNotEquals('test-value', $result);
        $this->assertEquals('test-value', Crypt::decrypt($result));
    }
}
