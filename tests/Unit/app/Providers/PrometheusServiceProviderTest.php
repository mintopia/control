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

    public function testRegisterHandlesNullRedisValues()
    {
        $gaugeMock = \Mockery::mock(\Spatie\Prometheus\MetricTypes\Gauge::class);
        $gaugeMock->shouldReceive('helpText')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('value')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('label')->atLeast()->zeroOrMoreTimes()->andReturnSelf();

        Prometheus::shouldReceive('addGauge')->atLeast()->once()->andReturn($gaugeMock);
        Redis::shouldReceive('get')->andReturn(null);
        $provider = new \app\Providers\PrometheusServiceProvider(app());
        $provider->register();
        $this->assertTrue(true);
    }

    public function testRegisterHandlesEmptyMethodAndStatusMetrics()
    {
        $gaugeMock = \Mockery::mock(\Spatie\Prometheus\MetricTypes\Gauge::class);
        $gaugeMock->shouldReceive('helpText')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('value')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('label')->atLeast()->once()->andReturnSelf();

        Prometheus::shouldReceive('addGauge')->atLeast()->once()->andReturn($gaugeMock);
        Redis::shouldReceive('keys')->andReturn([]);
        Redis::shouldReceive('mget')->andReturn([]);
        $provider = new \app\Providers\PrometheusServiceProvider(app());
        $provider->register();
        $this->assertTrue(true);
    }

    public function testRegisterHandlesMultipleMethodAndStatusMetrics()
    {
        $gaugeMock = \Mockery::mock(\Spatie\Prometheus\MetricTypes\Gauge::class);
        $gaugeMock->shouldReceive('helpText')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('value')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('label')->atLeast()->once()->andReturnSelf();

        Prometheus::shouldReceive('addGauge')->atLeast()->once()->andReturn($gaugeMock);
        Redis::shouldReceive('keys')->andReturn(['metrics.http.method.GET', 'metrics.http.method.POST']);
        Redis::shouldReceive('mget')->andReturn([5, 10]);
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

    public function testGetMultipleFromRedisEmptyKeysReturnsEmpty()
    {
        Redis::shouldReceive('keys')->andReturn([]);
        $provider = new \app\Providers\PrometheusServiceProvider(app());
        $result = $this->invokeProtected($provider, 'getMultipleFromRedis', ['metrics.http.method']);
        $this->assertEquals([], $result);
    }

    public function testRegisterHorizonCollectorsRegistersCollectors()
    {
        Prometheus::shouldReceive('registerCollectorClasses')->once()->withArgs(function ($arg) {
            return is_array($arg) && count($arg) === 7;
        });

        $provider = new \app\Providers\PrometheusServiceProvider(app());
        $result = $provider->registerHorizonCollectors();
        $this->assertInstanceOf(\app\Providers\PrometheusServiceProvider::class, $result);
    }

    private function invokeProtected($object, $method, $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}
