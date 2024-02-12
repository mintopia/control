<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SeatUpdateRequest;
use App\Models\Clan;
use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatingPlan;
use Illuminate\Http\Request;

class SeatController extends Controller
{
    public function create(Event $event, SeatingPlan $seatingplan)
    {
        $seat = new Seat();
        $seat->plan()->associate($seatingplan);
        return view('admin.seats.create', [
            'event' => $event,
            'plan' => $seatingplan,
            'seat' => $seat,
        ]);
    }

    public function store(SeatUpdateRequest $request, Event $event, SeatingPlan $seatingplan)
    {
        $seat = new Seat();
        $seat->plan()->associate($seatingplan);
        $this->updateObject($seat, $request);
        return response()->redirectToRoute('admin.events.seatingplans.show', [$event->code, $seatingplan->id])->with('successMessage', 'The seat has been created');
    }

    protected function updateObject(Seat $seat, Request $request): void
    {
        $seat->x = $request->input('x');
        $seat->y = $request->input('y');
        $seat->row = $request->input('row');
        $seat->number = $request->input('number');
        $seat->label = $request->input('label');
        $seat->description = $request->input('description');
        $seat->class = $request->input('class');
        $seat->disabled = (bool)$request->input('disabled', false);
        if($request->has('clan_id')){
            $clanId = $request->input('clan_id');
            $clan = Clan::whereId($clanId)->first();
            if($clan && $seat->clan != $clan){
                $seat->clan()->associate($clan);
            } else if(!$clan){
                $seat->clan()->disassociate();
            }
        }
        $seat->save();
    }

    public function show(Event $event, SeatingPlan $seatingplan, Seat $seat)
    {
        return view('admin.seats.show', [
            'event' => $event,
            'plan' => $seatingplan,
            'seat' => $seat
        ]);
    }

    public function edit(Event $event, SeatingPlan $seatingplan, Seat $seat)
    {
        return view('admin.seats.edit', [
            'event' => $event,
            'plan' => $seatingplan,
            'seat' => $seat,
            'clans' => Clan::all()
        ]);
    }

    public function update(SeatUpdateRequest $request, Event $event, SeatingPlan $seatingplan, Seat $seat)
    {
        $this->updateObject($seat, $request);
        return response()->redirectToRoute('admin.events.seatingplans.seats.show', [$event->code, $seatingplan->id, $seat->id])->with('successMessage', 'The seat has been updated');
    }

    public function destroy(Event $event, SeatingPlan $seatingplan, Seat $seat)
    {
        $seat->delete();
        return response()->redirectToRoute('admin.events.seatingplans.show', [$event->code, $seatingplan->id])->with('successMessage', 'The seat has been deleted');
    }

    public function delete(Event $event, SeatingPlan $seatingplan, Seat $seat)
    {
        return view('admin.seats.delete', [
            'event' => $event,
            'plan' => $seatingplan,
            'seat' => $seat,
        ]);
    }

    public function unseat(Event $event, SeatingPlan $seatingplan, Seat $seat)
    {
        $seat->ticket()->disassociate();
        $seat->save();
        return response()->redirectToRoute('admin.events.seatingplans.seats.show', [$event->code, $seatingplan->id, $seat->id])->with('successMessage', 'The ticket has been removed from this seat');
    }
}
