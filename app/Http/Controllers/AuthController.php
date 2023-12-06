<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->regenerate(true);
        return response()->redirectToRoute('home')->with('successMessage', 'You have been logged out');
    }

    public function login_redirect()
    {
        return Socialite::driver('discord')->redirect();
    }

    public function login_return()
    {
        try {
            $user = User::fromDiscord(Socialite::driver('discord')->user());
            if ($user) {
                Auth::login($user);
                return response()->redirectToIntended(route('home'))->with('successMessage', 'You have been logged in');
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
        }
        return response()->redirectToRoute('login')->with('errorMessage', 'Unable to login');
    }
}
