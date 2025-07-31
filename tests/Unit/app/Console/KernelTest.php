<?php

namespace Tests\Unit\app\Console;

use Tests\TestCase;
use App\Console\Kernel;
use Illuminate\Console\Scheduling\Schedule;

class KernelTest extends TestCase
{
    public function testCanInstantiateKernel()
    {
        $app = $this->createMock(\Illuminate\Contracts\Foundation\Application::class);
        $events = $this->createMock(\Illuminate\Contracts\Events\Dispatcher::class);
        $kernel = new Kernel($app, $events);
        $this->assertInstanceOf(Kernel::class, $kernel);
    }

    public function testScheduleContainsExpectedCommands()
    {
        $app = $this->createMock(\Illuminate\Contracts\Foundation\Application::class);
        $events = $this->createMock(\Illuminate\Contracts\Events\Dispatcher::class);
        $kernel = new Kernel($app, $events);
        $schedule = new Schedule();
        // Use reflection to call protected method
        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);
        $events = collect($schedule->events())->map(fn($e) => $e->command)->all();
        
        // FIXME the assertion fails here for some reason unknown to me - kernel might not be loaded correctly in the test environment?
        $this->assertContains('sanctum:prune-expired --hours=24', $events);
        $this->assertContains('telescope:prune', $events);
        $this->assertContains('control:prune-clans', $events);
        $this->assertContains('control:update-event-seating-locks', $events);
        $this->assertContains('control:sync-discord-roles', $events);
    }
}
