<?php

namespace Tests\Unit\app\Http\Controllers\Api\V1;

use Tests\TestCase;
use Mockery;
use App\Http\Controllers\Api\V1\SeatingPlanController;

class SeatingPlanControllerTest extends TestCase
{
    //FIXME Mocking for these classes does not work properlyu
    private function mockEvent(array $props = [])
    {
        $mock = Mockery::mock(\App\Models\Event::class);
        foreach ($props as $k => $v) {
            $mock->$k = $v;
        }
        return $mock;
    }
    private function mockTicket(array $methods = [], array $props = [])
    {
        $mock = Mockery::mock(\App\Models\Ticket::class);
        foreach ($methods as $method => $return) {
            $mock->shouldReceive($method)->andReturn($return);
        }
        foreach ($props as $k => $v) {
            $mock->$k = $v;
        }
        return $mock;
    }
    private function mockSeat()
    {
        return Mockery::mock(\App\Models\Seat::class);
    }
    private function mockPlan(array $props = [])
    {
        $mock = Mockery::mock(\App\Models\SeatingPlan::class);
        foreach ($props as $k => $v) {
            $mock->$k = $v;
        }
        return $mock;
    }
    private function mockBelongsTo($result)
    {
        $mock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        $mock->shouldReceive('getResults')->andReturn($result);
        return $mock;
    }

    public function testCanInstantiateController()
    {
        $controller = new SeatingPlanController();
        $this->assertInstanceOf(SeatingPlanController::class, $controller);
    }

    // // --- Tests for index ---
    // public function testIndexReturnsViewWithEventsForAdmin()
    // {
    //     $controller = new \App\Http\Controllers\SeatingPlanController();
    //     $mockUser = Mockery::mock();
    //     $mockUser->shouldReceive('hasAnyRole')->with(['admin', 'manager'])->once()->andReturn(true);
    //     $mockRequest = Mockery::mock(\Illuminate\Http\Request::class);
    //     $mockRequest->shouldReceive('user')->andReturn($mockUser);

    //     $mockQuery = Mockery::mock();
    //     $mockQuery->shouldReceive('orderBy')->with('starts_at', 'DESC')->once()->andReturnSelf();
    //     $mockQuery->shouldReceive('with')->with('seatingPlans')->once()->andReturnSelf();
    //     $mockQuery->shouldReceive('paginate')->once()->andReturn('paginated-events');

    //     Mockery::mock('alias:App\Models\Event')
    //         ->shouldReceive('query')
    //         ->andReturn($mockQuery);

    //     $response = $controller->index($mockRequest);
    //     $this->assertEquals(view('seatingplans.index', ['events' => 'paginated-events']), $response);
    // }

    // public function testIndexReturnsViewWithEventsForNonAdmin()
    // {
    //     $controller = new \App\Http\Controllers\SeatingPlanController();
    //     $mockUser = Mockery::mock();
    //     $mockUser->shouldReceive('hasAnyRole')->with(['admin', 'manager'])->once()->andReturn(false);
    //     $mockRequest = Mockery::mock(\Illuminate\Http\Request::class);
    //     $mockRequest->shouldReceive('user')->andReturn($mockUser);

    //     $mockQuery = Mockery::mock();
    //     $mockQuery->shouldReceive('whereDraft')->with(false)->once()->andReturnSelf();
    //     $mockQuery->shouldReceive('orderBy')->with('starts_at', 'DESC')->once()->andReturnSelf();
    //     $mockQuery->shouldReceive('with')->with('seatingPlans')->once()->andReturnSelf();
    //     $mockQuery->shouldReceive('paginate')->once()->andReturn('paginated-events');

    //     Mockery::mock('alias:App\Models\Event')
    //         ->shouldReceive('query')
    //         ->andReturn($mockQuery);

    //     $response = $controller->index($mockRequest);
    //     $this->assertEquals(view('seatingplans.index', ['events' => 'paginated-events']), $response);
    // }

    // // --- Tests for show ---
    // public function testShowRedirectsIfTicketCannotPickOrManage()
    // {
    //     $controller = new \App\Http\Controllers\SeatingPlanController();
    //     $mockUser = Mockery::mock();
    //     $mockUser->id = 1;
    //     $mockUser->shouldReceive('clanMemberships')->andReturnSelf();
    //     $mockUser->shouldReceive('hasAnyRole')->andReturn(true);
    //     $mockRequest = Mockery::mock(\Illuminate\Http\Request::class);
    //     $mockRequest->shouldReceive('user')->andReturn($mockUser);
    //     $mockRequest->shouldReceive('session')->andReturnSelf();
    //     $mockRequest->shouldReceive('isXmlHttpRequest')->andReturn(false);
    //     $mockRequest->shouldReceive('has')->andReturn(false);
    //     $mockEvent = $this->mockEvent(['seating_locked' => false, 'id' => 123]);
    //     $mockTicket = $this->mockTicket([
    //         'canPickSeat' => false,
    //         'canBeManagedBy' => false
    //     ]);
    //     $response = $controller->show($mockRequest, $mockEvent, $mockTicket);
    //     $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    // }

    // // --- Tests for select ---
    // public function testSelectAbortsIfSeatPlanEventMismatch()
    // {
    //     $controller = new \App\Http\Controllers\SeatingPlanController();
    //     $mockRequest = Mockery::mock(\Illuminate\Http\Request::class);
    //     $mockEvent = $this->mockEvent(['id' => 1]);
    //     $mockTicket = $this->mockTicket();
    //     $mockPlan = $this->mockPlan(['event_id' => 2, 'code' => 'plan1']);
    //     $mockSeat = $this->mockSeat();
    //     $mockBelongsTo = $this->mockBelongsTo($mockPlan);
    //     $mockSeat->shouldReceive('plan')->andReturn($mockBelongsTo);
    //     $mockSeat->shouldReceive('getAttribute')->with('plan')->andReturn($mockPlan);
    //     $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    //     $controller->select($mockRequest, $mockEvent, $mockTicket, $mockSeat);
    // }

    // public function testSelectAbortsIfTicketEventMismatch()
    // {
    //     $controller = new \App\Http\Controllers\SeatingPlanController();
    //     $mockRequest = Mockery::mock(\Illuminate\Http\Request::class);
    //     $mockEvent = $this->mockEvent(['id' => 1]);
    //     $mockTicket = $this->mockTicket([], ['event_id' => 2]);
    //     $mockPlan = $this->mockPlan(['event_id' => 1, 'code' => 'plan1']);
    //     $mockSeat = $this->mockSeat();
    //     $mockBelongsTo = $this->mockBelongsTo($mockPlan);
    //     $mockSeat->shouldReceive('plan')->andReturn($mockBelongsTo);
    //     $mockSeat->shouldReceive('getAttribute')->with('plan')->andReturn($mockPlan);
    //     $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    //     $controller->select($mockRequest, $mockEvent, $mockTicket, $mockSeat);
    // }

    // // --- Tests for unseat ---
    // public function testUnseatRedirectsIfCannotPickOrManage()
    // {
    //     $controller = new \App\Http\Controllers\SeatingPlanController();
    //     $mockUser = Mockery::mock();
    //     $mockRequest = Mockery::mock(\Illuminate\Http\Request::class);
    //     $mockRequest->shouldReceive('user')->andReturn($mockUser);
    //     $mockEvent = $this->mockEvent(['code' => 'event1', 'id' => 123]);
    //     $mockTicket = $this->mockTicket([
    //         'canPickSeat' => false,
    //         'canBeManagedBy' => false
    //     ]);
    //     $response = $controller->unseat($mockRequest, $mockEvent, $mockTicket);
    //     $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    // }


    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
