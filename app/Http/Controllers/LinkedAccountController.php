<?php

namespace App\Http\Controllers;

use App\Models\SocialProvider;

class LinkedAccountController extends Controller
{
    public function create(SocialProvider $socialprovider)
    {
        return $socialprovider->redirect();
    }

    public function store(SocialProvider $socialprovider)
    {
        $socialprovider->user();
        return response()->redirectToRoute('user.profile')->with('successMessage', "Your {$socialprovider->name} account has been linked");
    }
}
