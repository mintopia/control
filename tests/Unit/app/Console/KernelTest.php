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
        $descriptions = collect($schedule->events())->map(fn($e) => $e->description)->all();

        $this->assertContains('sanctum:prune-expired --hours=24', $descriptions);
        $this->assertContains('telescope:prune', $descriptions);
        $this->assertContains('control:prune-clans', $descriptions);
        $this->assertContains('control:update-event-seating-locks', $descriptions);
        $this->assertContains('control:sync-discord-roles', $descriptions);
    }

    // CHECK No such table clans - seeding missing?
    // Testing Function Commands
    // public function testFunctionCommands()
    // {
    //     $this->artisan('control:prune-clans')->assertSuccessful();
    //     $this->artisan('control:update-event-seating-locks')->assertSuccessful();
    //     $this->artisan('control:sync-discord-roles')->assertSuccessful();
    // }
}
