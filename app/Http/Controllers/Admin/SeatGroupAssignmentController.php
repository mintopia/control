<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\Admin\SeatGroupAssignmentUpdateRequest;
use App\Http\Requests\Admin\SeatGroupUpdateRequest;
use App\Models\Event;
use App\Models\SeatGroup;
use App\Models\SeatGroupAssignment;
use Illuminate\Http\Request;

class SeatGroupAssignmentController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Event $event, SeatGroup $seatgroup)
    {
        $assignment = new SeatGroupAssignment();
        $assignment->group()->associate($seatgroup);
        return view('admin.seatgroupassignments.create', [
            'event' => $event,
            'group' => $seatgroup
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SeatGroupAssignmentUpdateRequest $request, Event $event, SeatGroup $seatgroup)
    {
        $assignment = new SeatGroupAssignment();
        $assignment->group()->associate($seatgroup);
        $this->updateObject($assignment, $request);
        return response()->redirectToRoute('admin.events.seatgroups.show', [$event->code, $seatgroup->id])->with('successMessage', 'The seat group assignment has been created');
    }

    protected function updateObject(SeatGroupAssignment $assignment, Request $request)
    {
        $assignment->assignment_type = $request->input('assignment_type');
        $assignment->assignment_type_id = $request->input('assignment_type_id');
        $assignment->save();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event, SeatGroup $seatgroup, SeatGroupAssignment $assignment)
    {
        return view('admin.seatgroupassignments.edit', [
            'event' => $event,
            'group' => $seatgroup,
            'assignment' => $assignment
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SeatGroupAssignmentUpdateRequest $request, Event $event, SeatGroup $seatgroup, SeatGroupAssignment $assignment)
    {
        $this->updateObject($assignment, $request);
        return response()->redirectToRoute('admin.events.seatgroups.show', [$event->code, $seatgroup->id]);
    }

    public function destroy(DeleteRequest $request, Event $event, SeatGroup $seatgroup, SeatGroupAssignment $assignment)
    {
        $assignment->delete();
        return response()->redirectToRoute('admin.events.seatgroups.show', [$event->code, $seatgroup->id]);
    }

    public function delete(Event $event, SeatGroup $seatgroup, SeatGroupAssignment $assignment)
    {
        return view('admin.seatgroupassignments.delete', [
            'event' => $event,
            'group' => $seatgroup,
            'assignment' => $assignment,
        ]);
    }
}
