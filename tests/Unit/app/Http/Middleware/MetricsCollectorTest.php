<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\MetricsCollector;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class MetricsCollectorTest extends TestCase
{
    public function testHandleCallsStoreMetrics()
    {
        $middleware = $this->getMockBuilder(MetricsCollector::class)
            ->onlyMethods(['storeMetrics'])
            ->getMock();
        $middleware->expects($this->once())->method('storeMetrics')->with('GET', 200);
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestUri', 'getMethod'])
            ->getMock();
        $request->expects($this->any())->method('getRequestUri')->willReturn('/not-prometheus');
        $request->expects($this->any())->method('getMethod')->willReturn('GET');
        $response = $this->createMock(Response::class);
        $response->expects($this->any())->method('getStatusCode')->willReturn(200);
        $next = function () use ($response) {
            return $response;
        };
        $middleware->handle($request, $next);
    }

    public function testHandleDoesNotCallStoreMetricsForPrometheusUrl()
    {
        $middleware = $this->getMockBuilder(MetricsCollector::class)
            ->onlyMethods(['storeMetrics'])
            ->getMock();
        $middleware->expects($this->never())->method('storeMetrics');
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestUri', 'getMethod'])
            ->getMock();
        $request->expects($this->any())->method('getRequestUri')->willReturn('/metrics');
        $request->expects($this->any())->method('getMethod')->willReturn('GET');
        $response = $this->createMock(Response::class);
        $response->expects($this->any())->method('getStatusCode')->willReturn(200);
        $next = function () use ($response) {
            return $response;
        };
        // Simulate config value for prometheus.urls.default
        Config::shouldReceive('get')->with('prometheus.urls.default', null)->andReturn('metrics');
        $middleware->handle($request, $next);
    }

    public function testHandleCatchesExceptionFromStoreMetrics()
    {
        $middleware = $this->getMockBuilder(MetricsCollector::class)
            ->onlyMethods(['storeMetrics'])
            ->getMock();
        $middleware->expects($this->once())->method('storeMetrics')->willThrowException(new \Exception('fail'));
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRequestUri', 'getMethod'])
            ->getMock();
        $request->expects($this->any())->method('getRequestUri')->willReturn('/not-prometheus');
        $request->expects($this->any())->method('getMethod')->willReturn('POST');
        $response = $this->createMock(Response::class);
        $response->expects($this->any())->method('getStatusCode')->willReturn(500);
        $next = function () use ($response) {
            return $response;
        };
        // Suppress log output
        Log::shouldReceive('warning')->once()->withArgs(function ($msg) {
            return str_contains($msg, 'Unable to store metrics: fail');
        });
        $middleware->handle($request, $next);
    }

    public function testStoreMetricsIncrementsRedisKeys()
    {
        Redis::shouldReceive('incr')->with('metrics.http.method.GET', 1)->once();
        Redis::shouldReceive('incr')->with('metrics.http.status.201', 1)->once();
        Redis::shouldReceive('incr')->with('metrics.http.requests', 1)->once();
        $middleware = new class extends MetricsCollector {
            public function callStoreMetrics($method, $statusCode)
            {
                return $this->storeMetrics($method, $statusCode);
            }
        };
        $middleware->callStoreMetrics('GET', 201);
    }
}
