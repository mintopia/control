<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function home(Request $request)
    {
        $tickets = $request
            ->user()
            ->tickets()
            ->whereHas('event', function($query) use ($request)  {
                $query->where('ends_at', '>=', Carbon::now());
                if (!$request->user()->hasRole('admin')) {
                    $query->whereDraft(false);
                }
            })->with(['event' => function ($query) {
                $query->orderBy('starts_at', 'DESC');
            }, 'type', 'seat'])
            ->paginate();

        $query = Event::where('ends_at', '>=', Carbon::now());
        if (!$request->user()->hasRole('admin')) {
            $query->whereDraft(false);
        }
        $events = $query->get();

        return view('home.home', [
            'tickets' => $tickets,
            'events' => $events,
        ]);
    }
}
