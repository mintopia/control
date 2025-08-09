<?php

namespace Tests\Unit\app\Http\Controllers\Api\V1;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\SeatingPlanController;

class SeatingPlanControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new SeatingPlanController();
        $this->assertInstanceOf(SeatingPlanController::class, $controller);
    }

    // --- Tests for index ---
    public function testIndexReturnsViewWithEventsForAdmin()
    {
        $controller = new \App\Http\Controllers\SeatingPlanController();
        $mockUser = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['hasAnyRole'])
            ->getMock();
        $mockUser->expects($this->once())
            ->method('hasAnyRole')
            ->with(['admin', 'manager'])
            ->willReturn(true);
        $mockRequest = $this->getMockBuilder(\Illuminate\Http\Request::class)
            ->onlyMethods(['user'])
            ->getMock();
        $mockRequest->expects($this->any())->method('user')->willReturn($mockUser);

        $mockQuery = $this->getMockBuilder(\Illuminate\Database\Eloquent\Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['orderBy', 'with', 'paginate'])
            ->getMock();
        $mockQuery->expects($this->once())->method('orderBy')->with('starts_at', 'DESC')->willReturnSelf();
        $mockQuery->expects($this->once())->method('with')->with('seatingPlans')->willReturnSelf();
        $mockQuery->expects($this->once())->method('paginate')->willReturn('paginated-events');

        \App\Models\Event::shouldReceive('query')->andReturn($mockQuery);

        $response = $controller->index($mockRequest);
        $this->assertEquals(view('seatingplans.index', ['events' => 'paginated-events']), $response);
    }

    public function testIndexReturnsViewWithEventsForNonAdmin()
    {
        $controller = new \App\Http\Controllers\SeatingPlanController();
        $mockUser = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['hasAnyRole'])
            ->getMock();
        $mockUser->expects($this->once())
            ->method('hasAnyRole')
            ->with(['admin', 'manager'])
            ->willReturn(false);
        $mockRequest = $this->getMockBuilder(\Illuminate\Http\Request::class)
            ->onlyMethods(['user'])
            ->getMock();
        $mockRequest->expects($this->any())->method('user')->willReturn($mockUser);

        $mockQuery = $this->getMockBuilder(\Illuminate\Database\Eloquent\Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['whereDraft', 'orderBy', 'with', 'paginate'])
            ->getMock();
        $mockQuery->expects($this->once())->method('whereDraft')->with(false)->willReturnSelf();
        $mockQuery->expects($this->once())->method('orderBy')->with('starts_at', 'DESC')->willReturnSelf();
        $mockQuery->expects($this->once())->method('with')->with('seatingPlans')->willReturnSelf();
        $mockQuery->expects($this->once())->method('paginate')->willReturn('paginated-events');

        \App\Models\Event::shouldReceive('query')->andReturn($mockQuery);

        $response = $controller->index($mockRequest);
        $this->assertEquals(view('seatingplans.index', ['events' => 'paginated-events']), $response);
    }

    // --- Tests for show ---
    public function testShowRedirectsIfTicketCannotPickOrManage()
    {
        $controller = new \App\Http\Controllers\SeatingPlanController();
        $mockUser = $this->getMockBuilder(\stdClass::class)->addMethods(['hasAnyRole', 'clanMemberships', 'id'])->getMock();
        $mockUser->id = 1;
        $mockUser->expects($this->any())->method('clanMemberships')->willReturnSelf();
        $mockUser->expects($this->any())->method('hasAnyRole')->willReturn(true);
        $mockRequest = $this->getMockBuilder(\Illuminate\Http\Request::class)->onlyMethods(['user', 'session', 'isXmlHttpRequest', 'has', 'input'])->getMock();
        $mockRequest->expects($this->any())->method('user')->willReturn($mockUser);
        $mockRequest->expects($this->any())->method('session')->willReturnSelf();
        $mockRequest->expects($this->any())->method('isXmlHttpRequest')->willReturn(false);
        $mockRequest->expects($this->any())->method('has')->willReturn(false);
        $mockEvent = $this->getMockBuilder(\App\Models\Event::class)->disableOriginalConstructor()->getMock();
        $mockEvent->seating_locked = false;
        $mockTicket = $this->getMockBuilder(\App\Models\Ticket::class)->disableOriginalConstructor()->onlyMethods(['canPickSeat', 'canBeManagedBy'])->getMock();
        $mockTicket->expects($this->any())->method('canPickSeat')->willReturn(false);
        $mockTicket->expects($this->any())->method('canBeManagedBy')->willReturn(false);
        $response = $controller->show($mockRequest, $mockEvent, $mockTicket);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    // --- Tests for select ---
    public function testSelectAbortsIfSeatPlanEventMismatch()
    {
        $controller = new \App\Http\Controllers\SeatingPlanController();
        $mockRequest = $this->createMock(\Illuminate\Http\Request::class);
    $mockEvent = $this->getMockBuilder(\App\Models\Event::class)->disableOriginalConstructor()->getMock();
    $mockEvent->id = 1;
    $mockTicket = $this->getMockBuilder(\App\Models\Ticket::class)->disableOriginalConstructor()->getMock();
    $mockSeat = $this->getMockBuilder(\App\Models\Seat::class)->disableOriginalConstructor()->getMock();
    // Mock the plan relation to return a mock plan with event_id = 2
    $mockPlan = $this->getMockBuilder(\App\Models\SeatingPlan::class)->disableOriginalConstructor()->getMock();
    $mockPlan->event_id = 2;
    $mockPlan->code = 'plan1';
    $mockSeat->method('plan')->willReturn($mockPlan);
    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $controller->select($mockRequest, $mockEvent, $mockTicket, $mockSeat);
    }

    public function testSelectAbortsIfTicketEventMismatch()
    {
        $controller = new \App\Http\Controllers\SeatingPlanController();
        $mockRequest = $this->createMock(\Illuminate\Http\Request::class);
    $mockEvent = $this->getMockBuilder(\App\Models\Event::class)->disableOriginalConstructor()->getMock();
    $mockEvent->id = 1;
    $mockTicket = $this->getMockBuilder(\App\Models\Ticket::class)->disableOriginalConstructor()->getMock();
    $mockTicket->event_id = 2;
    $mockSeat = $this->getMockBuilder(\App\Models\Seat::class)->disableOriginalConstructor()->getMock();
    $mockPlan = $this->getMockBuilder(\App\Models\SeatingPlan::class)->disableOriginalConstructor()->getMock();
    $mockPlan->event_id = 1;
    $mockPlan->code = 'plan1';
    $mockSeat->method('plan')->willReturn($mockPlan);
    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $controller->select($mockRequest, $mockEvent, $mockTicket, $mockSeat);
    }

    // --- Tests for unseat ---
    public function testUnseatRedirectsIfCannotPickOrManage()
    {
        $controller = new \App\Http\Controllers\SeatingPlanController();
        $mockUser = $this->getMockBuilder(\stdClass::class)->addMethods(['id'])->getMock();
        $mockRequest = $this->getMockBuilder(\Illuminate\Http\Request::class)->onlyMethods(['user'])->getMock();
        $mockRequest->expects($this->any())->method('user')->willReturn($mockUser);
        $mockEvent = $this->getMockBuilder(\App\Models\Event::class)->disableOriginalConstructor()->getMock();
        $mockEvent->code = 'event1';
        $mockTicket = $this->getMockBuilder(\App\Models\Ticket::class)->disableOriginalConstructor()->onlyMethods(['canPickSeat', 'canBeManagedBy'])->getMock();
        $mockTicket->expects($this->any())->method('canPickSeat')->willReturn(false);
        $mockTicket->expects($this->any())->method('canBeManagedBy')->willReturn(false);
        $response = $controller->unseat($mockRequest, $mockEvent, $mockTicket);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }
}
