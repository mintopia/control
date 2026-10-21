<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\HomeController;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new HomeController();
        $this->assertInstanceOf(HomeController::class, $controller);
    }

    public function testDashboardReturnsExpectedViewAndData()
    {
        // create some users and upcoming events so the dashboard has data
        User::factory()->count(3)->create();
        $futureEvent = Event::factory()->create([
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);

        $controller = new HomeController();

        $response = $controller->dashboard();

        // view() helper returns an instance of \Illuminate\View\View
        $this->assertInstanceOf(View::class, $response);

        $data = $response->getData();

        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('events', $data);
        $this->assertNotEmpty($data['events']);

        // ensure the future event is included
        $this->assertTrue(collect($data['events'])->contains('id', $futureEvent->id));
    }

    public function testUnimpersonateWithoutImpersonatingAborts()
    {
        $this->expectException(HttpException::class);

        $controller = new HomeController();
        $request = Request::create('/', 'GET');
        $request->setLaravelSession(app('session.store'));

        // no 'impersonating' flag in session -> should abort with 403
        $controller->unimpersonate($request);
    }

    public function testUnimpersonateRedirectsToUserShow()
    {
        // create a route so route generation works during the test
        Route::get('admin/users/{user}', function () {
            return 'ok';
        })->name('admin.users.show');

        $originalUser = User::factory()->create();
        $impersonated = User::factory()->create();

        $request = Request::create('/', 'GET');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('impersonating', true);
        $request->session()->put('originalUserId', $originalUser->id);

        // make sure request()->user() returns the impersonated user
        $this->be($impersonated);
        $request->setUserResolver(function () use ($impersonated) {
            return $impersonated;
        });

        $controller = new HomeController();

        $response = $controller->unimpersonate($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(route('admin.users.show', $impersonated->id), $response->getTargetUrl());
    }
}
