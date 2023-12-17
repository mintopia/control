<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialProvider;
use App\Models\TicketProvider;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $socialProviders = SocialProvider::get();
        $ticketProviders = TicketProvider::get();
        return view('admin.settings.index', [
            'socialProviders' => $socialProviders,
            'ticketProviders' => $ticketProviders,
        ]);
    }


}
