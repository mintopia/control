<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\Admin\SeatingPlanImportRequest;
use App\Http\Requests\Admin\SeatingPlanUpdateRequest;
use App\Models\Event;
use App\Models\SeatingPlan;
use Carbon\Carbon;
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

    protected function updateObject(SeatingPlan $plan, Request $request)
    {
        $plan->name = $request->input('name');
        $plan->image_url = $request->input('image_url');
        $plan->scale = $request->input('scale') ? $request->input('scale') : 100;
        $plan->save();
    }

    public function show(Event $event, SeatingPlan $seatingplan)
    {
        return view('admin.seatingplans.show', [
            'event' => $event,
            'plan' => $seatingplan,
            'seats' => $seatingplan->getData(),
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

    public function destroy(DeleteRequest $request, Event $event, SeatingPlan $seatingplan)
    {
        $seatingplan->delete();
        return response()->redirectToRoute('admin.events.show', $event->code)->with('successMessage', 'The seating plan has been deleted');
    }

    public function delete(Event $event, SeatingPlan $seatingplan)
    {
        return view('admin.seatingplans.delete', [
            'event' => $event,
            'plan' => $seatingplan,
        ]);
    }

    public function refresh(Event $event, SeatingPlan $seatingplan)
    {
        $seatingplan->updateRevision();
        return response()->redirectToRoute('admin.events.seatingplans.show', [$event->code, $seatingplan->id])->with('successMessage', 'The seating plan will be refreshed');
    }

    public function up(Event $event, SeatingPlan $seatingplan)
    {
        $seatingplan->moveOrderUp();
        return response()->redirectToRoute('admin.events.show', $event->code)->with('successMessage', 'The seating plan has been moved up');
    }

    public function down(Event $event, SeatingPlan $seatingplan)
    {
        $seatingplan->moveOrderDown();
        return response()->redirectToRoute('admin.events.show', $event->code)->with('successMessage', 'The seating plan has been moved down');
    }

    public function export(Event $event, SeatingPlan $seatingplan)
    {
        $csv = [[
            'ID', 'X', 'Y', 'Row', 'Number', 'Label', 'Description', 'CSS Class', 'Disabled',
        ]];
        $seatingplan->seats()->chunk(100, function ($chunk) use (&$csv) {
            foreach ($chunk as $seat) {
                $csv[] = [
                    $seat->id,
                    $seat->x,
                    $seat->y,
                    $seat->row,
                    $seat->number,
                    $seat->label,
                    $seat->description,
                    $seat->class,
                    $seat->disabled ? 1 : 0,
                ];
            }
        });

        $filename = "seating-{$seatingplan->id}-seats-" . Carbon::now()->format('YmdHis') . ".csv";
        return response()->streamDownload(function () use ($csv) {
            $handle = fopen('php://output', 'w');
            foreach ($csv as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename);
    }

    public function import_process(SeatingPlanImportRequest $request, Event $event, SeatingPlan $seatingplan)
    {
        $csv = $request->file('csv')->get();
        $wipe = (bool)$request->input('wipe', false);
        $seatingplan->import($csv, $wipe);
        return response()->redirectToRoute('admin.events.seatingplans.show', [$event->code, $seatingplan->id])->with('successMessage', 'The seating plan has been imported');
    }

    public function import(Event $event, SeatingPlan $seatingplan)
    {
        return view('admin.seatingplans.import', [
            'event' => $event,
            'plan' => $seatingplan,
        ]);
    }
}
