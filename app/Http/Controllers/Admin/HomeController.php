<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function dashboard()
    {
        $stats = (object)[
            'users' => (object)[
                'total' => User::count(),
                'lastWeek' => User::where('created_at', '>', Carbon::now()->subWeek())->count(),
            ]
        ];

        return view('admin.home.dashboard', [
            'stats' => $stats,
            'events' => Event::where('ends_at', '>=', Carbon::now())->orderBy('starts_at', 'ASC')->get(),
        ]);
    }
}
