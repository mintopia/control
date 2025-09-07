<?php

namespace Tests\Unit\app\Console;

use App\Console\Kernel;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class KernelTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateKernel()
    {
        $app = $this->createMock(Application::class);
        $events = $this->createMock(Dispatcher::class);
        $kernel = new Kernel($app, $events);
        $this->assertInstanceOf(Kernel::class, $kernel);
    }

    public function testScheduleContainsExpectedCommands()
    {
        $app = $this->createMock(Application::class);
        $events = $this->createMock(Dispatcher::class);
        $kernel = new Kernel($app, $events);
        $schedule = new Schedule();
        // Use reflection to call protected method
        $reflection = new ReflectionClass($kernel);
        $method = $reflection->getMethod('schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);
        $commands = collect($schedule->events())->map(function (Event $event) {
            $pos = strpos($event->command, 'artisan');
            return substr($event->command, $pos + 9);
        })->all();

        $this->assertContains('sanctum:prune-expired --hours=24', $commands);
        $this->assertContains('telescope:prune', $commands);
        $this->assertContains('control:prune-clans', $commands);
        $this->assertContains('control:update-event-seating-locks', $commands);
        $this->assertContains('control:sync-discord-roles', $commands);
    }

    public function testFunctionCommands()
    {
        $this->artisan('control:prune-clans')->assertSuccessful();
        $this->artisan('control:update-event-seating-locks')->assertSuccessful();
        $this->artisan('control:sync-discord-roles')->assertSuccessful();
    }
}
