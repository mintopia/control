<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EventController;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new EventController();
        $this->assertInstanceOf(EventController::class, $controller);
    }

    public function testCreateEventStoresAndRedirects()
    {
        $controller = new EventController();

        $request = Event::factory()->make([
            'name' => 'Test Event',
            'starts_at' => '2025-08-07 10:00:00',
            'ends_at' => '2025-08-07 12:00:00',
        ]);

        // Use updateObject indirectly by calling store with a manually created request object
        $response = $controller->store(\App\Http\Requests\Admin\EventUpdateRequest::create('/', 'POST', $request->toArray()));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseHas('events', ['name' => 'Test Event']);
    }

    public function testUpdateEventModifiesAndRedirects()
    {
        $event = Event::factory()->create([
            'name' => 'Original',
            'starts_at' => '2025-08-07 10:00:00',
            'ends_at' => '2025-08-07 12:00:00',
        ]);

        $controller = new EventController();

        $requestData = [
            'name' => 'Updated Event',
            'starts_at' => '2025-08-08 10:00:00',
            'ends_at' => '2025-08-08 12:00:00',
        ];

        $response = $controller->update(\App\Http\Requests\Admin\EventUpdateRequest::create('/', 'POST', $requestData), $event);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'name' => 'Updated Event']);
    }

    public function testDeleteEventDeletesAndRedirects()
    {
        $event = Event::factory()->create();
        $controller = new EventController();

        // Delete request requires confirm field; use a stubbed DeleteRequest with required data
        $response = $controller->destroy(\App\Http\Requests\Admin\DeleteRequest::create('/', 'DELETE', ['confirm' => 'delete']), $event);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }
}
