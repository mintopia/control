<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\MetricsCollector;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MetricsCollectorTest extends TestCase
{
    public function testHandleCallsStoreMetrics()
    {
        $middleware = $this->getMockBuilder(MetricsCollector::class)
            ->onlyMethods(['storeMetrics'])
            ->getMock();
        $middleware->expects($this->once())->method('storeMetrics')->with('GET', 200);
        $request = $this->createMock(Request::class);
        $request->method('getRequestUri')->willReturn('/not-prometheus');
        $request->method('getMethod')->willReturn('GET');
        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn(200);
        $next = function () use ($response) {
            return $response;
        };
        $middleware->handle($request, $next);
    }
}
