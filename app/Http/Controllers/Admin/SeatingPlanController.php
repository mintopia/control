<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\Admin\SeatingPlanUpdateRequest;
use App\Models\Event;
use App\Models\SeatingPlan;
use Illuminate\Http\Request;

class SeatingPlanController extends Controller
{
    public function create(Event $event)
    {
        $seatingplan = new SeatingPlan();
        $seatingplan->event()->associate($event);
        return view('admin.seatingplans.create', [
            'event' => $event,
            'plan' => $seatingplan,
        ]);
    }

    public function store(SeatingPlanUpdateRequest $request, Event $event)
    {
        $seatingplan = new SeatingPlan();
        $seatingplan->event()->associate($event);
        $this->updateObject($seatingplan, $request);
        return response()->redirectToRoute('admin.events.seatingplans.show', [$event->code, $seatingplan->id])->with('successMessage', 'The seating plan has been created');
    }

    public function show(Event $event, SeatingPlan $seatingplan)
    {
        return view('admin.seatingplans.show', [
            'event' => $event,
            'plan' => $seatingplan,
        ]);
    }

    public function edit(Event $event, SeatingPlan $seatingplan)
    {
        return view('admin.seatingplans.edit', [
            'event' => $event,
            'plan' => $seatingplan,
        ]);
    }

    public function update(SeatingPlanUpdateRequest $request, Event $event, SeatingPlan $seatingplan)
    {
        $this->updateObject($seatingplan, $request);
        return response()->redirectToRoute('admin.events.seatingplans.show', [$event->code, $seatingplan->id])->with('successMessage', 'The seating plan has been updated');
    }

    public function delete(Event $event, SeatingPlan $seatingplan)
    {
        return view('admin.seatingplans.delete', [
            'event' => $event,
            'plan' => $seatingplan,
        ]);
    }

    public function destroy(DeleteRequest$request, Event $event, SeatingPlan $seatingplan)
    {
        $seatingplan->delete();
        return response()->redirectToRoute('admin.events.show', $event->code)->with('successMessage', 'The seating plan has been deleted');
    }

    public function refresh(Event $event, SeatingPlan $seatingplan)
    {
        $seatingplan->updateRevision();
        return response()->redirectToRoute('admin.events.seatingplans.show', [$event->code, $seatingplan->id])->with('successMessage', 'The seating plan will be refreshed');
    }

    public function up(Event $event, SeatingPlan $seatingplan)
    {
        $other = $event->seatingPlans()->where('order', '<', $seatingplan->order)->orderBy('order', 'DESC')->first();
        if ($other) {
            $other->order++;
            $seatingplan->order--;
            $other->saveQuietly();
            $seatingplan->saveQuietly();
        }
        $event->fixSeatingPlanOrder();
        return response()->redirectToRoute('admin.events.show', $event->code)->with('successMessage', 'The seating plan has been moved up');
    }

    public function down(Event $event, SeatingPlan $seatingplan)
    {
        $other = $event->seatingPlans()->where('order', '>', $seatingplan->order)->orderBy('order', 'ASC')->first();
        if ($other) {
            $other->order--;
            $seatingplan->order++;
            $other->saveQuietly();
            $seatingplan->saveQuietly();
        }
        $event->fixSeatingPlanOrder();
        return response()->redirectToRoute('admin.events.show', $event->code)->with('successMessage', 'The seating plan has been moved down');
    }

    protected function updateObject(SeatingPlan $plan, Request $request)
    {
        $plan->name = $request->input('name');
        $plan->image_url = $request->input('image_url');
        $plan->save();
    }
}
