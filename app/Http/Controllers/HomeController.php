<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function home(Request $request)
    {
        $tickets = $request->user()->tickets()->with(['event' => function($query) {
            $query->orderBy('starts_at', 'DESC');
            $query->where('ends_at', '>=', Carbon::now());
        }, 'type', 'seat'])->paginate();

        $events = Event::where('starts_at', '>=', Carbon::now())->get();

        return view('home.home', [
            'tickets' => $tickets,
            'events' => $events,
        ]);
    }
}
