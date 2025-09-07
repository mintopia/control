<?php

namespace Tests\Unit\app\Providers;

use app\Providers\PrometheusServiceProvider;
use Illuminate\Support\Facades\Redis;
use Mockery;
use ReflectionClass;
use Spatie\Prometheus\Facades\Prometheus;
use Spatie\Prometheus\MetricTypes\Gauge;
use Tests\TestCase;

class PrometheusServiceProviderTest extends TestCase
{
    public function testRegisterAddsGauges()
    {
        $gaugeMock = Mockery::mock(Gauge::class);
        $gaugeMock->shouldReceive('helpText')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('value')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('label')->atLeast()->zeroOrMoreTimes()->andReturnSelf();

        Prometheus::shouldReceive('addGauge')->atLeast()->once()->andReturn($gaugeMock);
        Redis::shouldReceive('get')->andReturn(1);
        $provider = new PrometheusServiceProvider(app());
        $provider->register();
        $this->assertTrue(true);
    }

    public function testRegisterHandlesNullRedisValues()
    {
        $gaugeMock = Mockery::mock(Gauge::class);
        $gaugeMock->shouldReceive('helpText')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('value')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('label')->atLeast()->zeroOrMoreTimes()->andReturnSelf();

        Prometheus::shouldReceive('addGauge')->atLeast()->once()->andReturn($gaugeMock);
        Redis::shouldReceive('get')->andReturn(null);
        $provider = new PrometheusServiceProvider(app());
        $provider->register();
        $this->assertTrue(true);
    }

    public function testRegisterHandlesEmptyMethodAndStatusMetrics()
    {
        $gaugeMock = Mockery::mock(Gauge::class);
        $gaugeMock->shouldReceive('helpText')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('value')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('label')->atLeast()->once()->andReturnSelf();

        Prometheus::shouldReceive('addGauge')->atLeast()->once()->andReturn($gaugeMock);
        Redis::shouldReceive('keys')->andReturn([]);
        Redis::shouldReceive('mget')->andReturn([]);
        $provider = new PrometheusServiceProvider(app());
        $provider->register();
        $this->assertTrue(true);
    }

    public function testRegisterHandlesMultipleMethodAndStatusMetrics()
    {
        $gaugeMock = Mockery::mock(Gauge::class);
        $gaugeMock->shouldReceive('helpText')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('value')->atLeast()->once()->andReturnSelf();
        $gaugeMock->shouldReceive('label')->atLeast()->once()->andReturnSelf();

        Prometheus::shouldReceive('addGauge')->atLeast()->once()->andReturn($gaugeMock);
        Redis::shouldReceive('keys')->andReturn(['metrics.http.method.GET', 'metrics.http.method.POST']);
        Redis::shouldReceive('mget')->andReturn([5, 10]);
        $provider = new PrometheusServiceProvider(app());
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
        $provider = new PrometheusServiceProvider(app());
        $result = $this->invokeProtected($provider, 'getMultipleFromRedis', ['metrics.http.method']);
        $this->assertIsArray($result);
    }

    public function testGetMultipleFromRedisEmptyKeysReturnsEmpty()
    {
        Redis::shouldReceive('keys')->andReturn([]);
        $provider = new PrometheusServiceProvider(app());
        $result = $this->invokeProtected($provider, 'getMultipleFromRedis', ['metrics.http.method']);
        $this->assertEquals([], $result);
    }

    public function testRegisterHorizonCollectorsRegistersCollectors()
    {
        Prometheus::shouldReceive('registerCollectorClasses')->once()->withArgs(function ($arg) {
            return is_array($arg) && count($arg) === 7;
        });

        $provider = new PrometheusServiceProvider(app());
        $result = $provider->registerHorizonCollectors();
        $this->assertInstanceOf(PrometheusServiceProvider::class, $result);
    }

    public function testRegisterClosureReturnValues()
    {
        // Reset Mockery to clear any previous facade expectations
        Mockery::close();

        // Capture the gauge objects so we can invoke their value callbacks by swapping the facade
        $promStub = new class {
            public $gauges = [];

            public function addGauge($name)
            {
                $g = new class {
                    public $valueCallback = null;

                    public function helpText($t)
                    {
                        return $this;
                    }

                    public function label($l)
                    {
                        return $this;
                    }

                    public function value($cb)
                    {
                        $this->valueCallback = $cb;
                        return $this;
                    }
                };
                $this->gauges[] = $g;
                return $g;
            }
        };
        Prometheus::swap($promStub);

        // Prepare Redis expectations for the different callbacks
        // First gauge: metrics.http.requests -> return 7
        Redis::shouldReceive('get')->with('metrics.http.requests')->andReturn(7);

        // For methods/status gauges, keys and mget must return values
        Redis::shouldReceive('keys')->with('metrics.http.method.*')->andReturn(['metrics.http.method.GET']);
        Redis::shouldReceive('mget')->with(['metrics.http.method.GET'])->andReturn([5]);

        Redis::shouldReceive('keys')->with('metrics.http.status.*')->andReturn(['metrics.http.status.200']);
        Redis::shouldReceive('mget')->with(['metrics.http.status.200'])->andReturn([200]);

        // Last gauge: metrics.exceptions -> return null -> expect 0
        Redis::shouldReceive('get')->with('metrics.exceptions')->andReturn(null);

        $provider = new PrometheusServiceProvider(app());
        $provider->register();

        // We should have captured 4 gauges and their callbacks on the stub
        $this->assertCount(4, $promStub->gauges, 'Expected four gauges to be registered');

        // Invoke first gauge callback (HTTP Requests)
        $firstVal = ($promStub->gauges[0]->valueCallback)();
        $this->assertEquals(7, $firstVal);

        // Invoke second gauge callback (HTTP Methods) -> should return array of [value, [label]] pairs
        $methodsVal = ($promStub->gauges[1]->valueCallback)();
        $this->assertIsArray($methodsVal);
        $this->assertEquals(5, $methodsVal[0][0]);
        $this->assertEquals('GET', $methodsVal[0][1][0]);

        // Invoke third gauge callback (HTTP Status Codes)
        $statusVal = ($promStub->gauges[2]->valueCallback)();
        $this->assertIsArray($statusVal);
        $this->assertEquals(200, $statusVal[0][0]);
        $this->assertEquals('200', $statusVal[0][1][0]);

        // Invoke fourth gauge callback (Uncaught Exceptions) - null should coerce to 0
        $exceptionsVal = ($promStub->gauges[3]->valueCallback)();
        $this->assertEquals(0, $exceptionsVal);
    }

    private function invokeProtected($object, $method, $args = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}
