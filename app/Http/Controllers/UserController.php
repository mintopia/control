<?php

namespace App\Http\Controllers;

use App\Models\SocialProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        return view('users.profile');
    }

    public function login()
    {
        $providers = SocialProvider::whereAuthEnabled(true)->whereEnabled(true)->get();
        return view('users.login', [
            'providers' => $providers,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->regenerate(true);
        return response()->redirectToRoute('home')->with('successMessage', 'You have been logged out');
    }

    public function login_redirect(SocialProvider $socialprovider)
    {
        if (!$socialprovider->enabled || !$socialprovider->auth_enabled) {
            return response()->redirectToRoute('login')->with('errorMessage', 'Unable to login');
        }
        return $socialprovider->redirect();
    }

    public function login_return(SocialProvider $socialprovider)
    {
        if (!$socialprovider->enabled || !$socialprovider->auth_enabled) {
            return response()->redirectToRoute('login')->with('errorMessage', 'Unable to login');
        }
        try {
            $user = $socialprovider->user();
            if ($user) {
                Auth::login($user);
                return response()->redirectToIntended(route('home'))->with('successMessage', 'You have been logged in');
            }
        } catch (\Exception $ex) {
            throw $ex;
            Log::error($ex->getMessage());
        }
        return response()->redirectToRoute('login')->with('errorMessage', 'Unable to login');
    }
}
