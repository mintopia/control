<?php

namespace Tests\Unit\app\Providers;

use Tests\TestCase;

use Illuminate\Support\Facades\Redis;
use Spatie\Prometheus\Facades\Prometheus;

class PrometheusServiceProviderTest extends TestCase
{
    public function testRegisterAddsGauges()
    {
        $gaugeMock = \Mockery::mock(\Spatie\Prometheus\MetricTypes\Gauge::class);
        $gaugeMock->shouldReceive('helpText')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('value')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('label')->atLeast()->zeroOrMoreTimes()->andReturnSelf();

        Prometheus::shouldReceive('addGauge')->atLeast()->once()->andReturn($gaugeMock);
        Redis::shouldReceive('get')->andReturn(1);
        $provider = new \app\Providers\PrometheusServiceProvider(app());
        $provider->register();
        $this->assertTrue(true);
    }

    public function testGetMultipleFromRedisReturnsArray()
    {
        Redis::shouldReceive('keys')->andReturnUsing(function () {
            return ['metrics.http.method.GET', 'metrics.http.method.POST'];
        });
        Redis::shouldReceive('mget')->andReturnUsing(function () {
            return [5, 10];
        });
        $provider = new \app\Providers\PrometheusServiceProvider(app());
        $result = $this->invokeProtected($provider, 'getMultipleFromRedis', ['metrics.http.method']);
        $this->assertIsArray($result);
    }

    private function invokeProtected($object, $method, $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}
