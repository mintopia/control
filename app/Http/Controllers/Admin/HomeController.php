<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function dashboard()
    {
        return view('admin.home.dashboard', [
            'userCount' => User::count(),
            'ticketsCount' => Ticket::count(),
        ]);
    }
}
