<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailAddressRequest;
use App\Http\Requests\EmailVerifyRequest;
use App\Models\EmailAddress;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmailAddressController extends Controller
{
    public function create()
    {
        return view('emails.create');
    }

    public function store(EmailAddressRequest $request)
    {
        // If the email exists and is not verified, remove it
        $email = EmailAddress::whereEmail($request->input('email'))->whereNull('verified_at')->first();
        if ($email && $email->user_id !== $request->user()->id) {
            $email->delete();
        }

        if (!$email) {
            $email = new EmailAddress();
            $email->email = $request->input('email');
            $email->user()->associate($request->user());
            $email->save();
        }
        $email->sendVerificationCode();
        return response()->redirectToRoute('emails.verify', $email->id)->with('successMessage', "A verification code has been sent to {$email->email}");
    }

    public function verify(EmailAddress $emailaddress)
    {
        if ($emailaddress->verified_at) {
            return response()->redirectToRoute('user.profile')->with('successMessage', 'The email address is verified');
        }
        return view('emails.verify', [
            'email' => $emailaddress,
        ]);
    }

    public function verify_resend(EmailAddress $emailaddress)
    {
        if ($emailaddress->verified_at) {
            return response()->redirectToRoute('user.profile')->with('successMessage', 'The email address is verified');
        }
        $emailaddress->sendVerificationCode();
        return response()->redirectToRoute('emails.verify', $emailaddress->id)->with('successMessage', "A verification code has been sent to {$emailaddress->email}");
    }

    public function verify_process(EmailVerifyRequest $request, EmailAddress $emailaddress)
    {
        if ($emailaddress->verified_at) {
            return response()->redirectToRoute('user.profile')->with('successMessage', 'The email address is verified');
        }
        if ($request->input('code') === $emailaddress->verification_code) {
            $emailaddress->verified_at = Carbon::now();
            $emailaddress->save();
            return response()->redirectToRoute('user.profile')->with('successMessage', 'The email address has been verified');
        } else {
            return response()->redirectToRoute('emails.verify', $emailaddress->id)->with('errorMessage', 'The code you entered was incorrect');
        }
    }

    public function delete(EmailAddress $emailaddress)
    {
        if (!$emailaddress->canDelete()) {
            return response()->redirectToRoute('user.profile')->with('errorMessage', 'It is not possible to remove this email address');
        }
        return view('emails.delete', [
            'email' => $emailaddress,
        ]);
    }

    public function destroy(EmailAddress $emailaddress)
    {
        if (!$emailaddress->canDelete()) {
            return response()->redirectToRoute('user.profile')->with('errorMessage', 'It is not possible to remove this email address');
        }
        $emailaddress->delete();
        return response()->redirectToRoute('user.profile')->with('successMessage', 'The email address has been removed');
    }
}
