<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\Admin\SeatGroupUpdateRequest;
use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatGroup;
use Illuminate\Http\Request;

class SeatGroupController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Event $event)
    {
        $group = new SeatGroup();
        $group->event()->associate($event);
        return view('admin.seatgroups.create', [
            'event' => $event
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SeatGroupUpdateRequest $request, Event $event)
    {
        $group = new SeatGroup();
        $group->event()->associate($event);
        $this->updateObject($group, $request);
        return response()->redirectToRoute('admin.events.seatgroups.show', [$event->code, $group->id])->with('successMessage', 'The seat group has been created');
    }

    protected function updateObject(SeatGroup $group, Request $request)
    {
        $group->name = $request->input('name');
        $group->class = $request->input('class');
        $group->save();
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event, SeatGroup $seatgroup)
    {
        return view('admin.seatgroups.show', [
            'event' => $event,
            'group' => $seatgroup
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event, SeatGroup $seatgroup)
    {
        return view('admin.seatgroups.edit', [
            'event' => $event,
            'group' => $seatgroup
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SeatGroupUpdateRequest $request, Event $event, SeatGroup $seatgroup)
    {
        $this->updateObject($seatgroup, $request);
        return response()->redirectToRoute('admin.events.seatgroups.show', [$event->code, $seatgroup->id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteRequest $request, Event $event, SeatGroup $seatgroup)
    {
        $seatgroup->delete();
        return response()->redirectToRoute('admin.events.show', $event->code);
    }

    public function delete(Event $event, SeatGroup $seatgroup)
    {
        return view('admin.seatgroups.delete', [
            'event' => $event,
            'group' => $seatgroup,
        ]);
    }
}
