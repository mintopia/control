<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmailAddressUpdateRequest;
use App\Models\EmailAddress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmailAddressController extends Controller
{
    public function create(User $user)
    {
        $email = new EmailAddress();
        $email->user()->associate($user);
        return view('admin.emailaddresses.create', [
            'email' => $email,
        ]);
    }

    public function store(User $user, EmailAddressUpdateRequest $request)
    {
        $email = new EmailAddress();
        $email->user()->associate($request->user());
        $this->updateObject($email, $request);
        return response()->redirectToRoute('admin.users.show', $email->user->id)->with('successMessage', 'The email address has been created');
    }

    protected function updateObject(EmailAddress $email, Request $request)
    {
        $email->email = $request->input('address');
        if ($request->input('verified', false)) {
            if (!$email->verified_at) {
                $email->verified_at = Carbon::now();
            }
        } else {
            $email->verified_at = null;
        }
        $email->save();
    }

    public function edit(User $user, EmailAddress $email)
    {
        return view('admin.emailaddresses.edit', [
            'email' => $email,
        ]);
    }

    public function update(EmailAddressUpdateRequest $request, User $user, EmailAddress $email)
    {
        $this->updateObject($email, $request);
        return response()->redirectToRoute('admin.users.show', $email->user->id)->with('successMessage', 'The email address has been updated');
    }

    public function destroy(User $user, EmailAddress $email)
    {
        if (!$email->canDelete()) {
            return response()->redirectToRoute('admin.users.show')->with('errorMessage', 'It is not possible to remove this email address');
        }
        $email->delete();
        return response()->redirectToRoute('admin.users.show', $email->user->id)->with('successMessage', 'The email address has been deleted');
    }

    public function delete(User $user, EmailAddress $email)
    {
        if (!$email->canDelete()) {
            return response()->redirectToRoute('admin.users.show')->with('errorMessage', 'It is not possible to remove this email address');
        }
        return view('admin.emailaddresses.delete', [
            'email' => $email,
        ]);
    }
}
