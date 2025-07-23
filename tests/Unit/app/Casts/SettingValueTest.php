<?php

namespace Tests\Unit;

use App\Casts\SettingValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\TestCase;
use Mockery;

class SettingValueTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_returns_decrypted_value_when_encrypted()
    {
        $model = Mockery::mock(Model::class);
        $model->encrypted = true;
        $model->shouldReceive('setAttribute')->andReturnNull();
        $encryptedValue = 'encrypted';
        Crypt::shouldReceive('decrypt')->once()->with($encryptedValue)->andReturn('decrypted');

        $cast = new SettingValue();
        $result = $cast->get($model, 'key', $encryptedValue, []);
        $this->assertEquals('decrypted', $result);
    }

    public function test_get_returns_value_when_not_encrypted()
    {
        $model = Mockery::mock(Model::class);
        $model->encrypted = false;
        $model->shouldReceive('setAttribute')->andReturnNull();
        $value = 'plain';

        $cast = new SettingValue();
        $result = $cast->get($model, 'key', $value, []);
        $this->assertEquals('plain', $result);
        $this->assertEquals('plain', $result);
    }

    public function test_set_returns_encrypted_value_when_encrypted()
    {
        $model = Mockery::mock(Model::class);
        $model->encrypted = true;
        $model->shouldReceive('setAttribute')->andReturnNull();
        $value = 'plain';
        Crypt::shouldReceive('encrypt')->once()->with($value)->andReturn('encrypted');

        $cast = new SettingValue();
        $result = $cast->set($model, 'key', $value, []);
        $this->assertEquals('encrypted', $result);
    }

    public function test_set_returns_value_when_not_encrypted()
    {
        $model = Mockery::mock(Model::class);
        $model->encrypted = false;
        $model->shouldReceive('setAttribute')->andReturnNull();
        $value = 'plain';

        $cast = new SettingValue();
        $result = $cast->set($model, 'key', $value, []);
        $this->assertEquals('plain', $result);
    }
}
