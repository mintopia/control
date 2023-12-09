<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClanMembershipRequest;
use App\Http\Requests\ClanMembershipUpdateRequest;
use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\ClanRole;
use Illuminate\Support\Facades\Auth;

class ClanMembershipController extends Controller
{
    public function store(ClanMembershipRequest $request)
    {
        $clan = Clan::whereInviteCode($request->input('code'))->first();
        if ($clan->isMember($request->user())) {
            $message = "You are already a member of {$clan->name}";
        } else {
            $message = "You have joined {$clan->name}";
            $clan->addUser($request->user());
        }
        return response()->redirectToRoute('clans.show', $clan->code)->with('successMessage', $message);
    }

    public function edit(Clan $clan, ClanMembership $clanmembership)
    {
        return view('clans.members.edit', [
            'clan' => $clan,
            'member' => $clanmembership
        ]);
    }

    public function update(ClanMembershipUpdateRequest $request, Clan $clan, ClanMembership $clanmembership)
    {
        $role = ClanRole::whereCode($request->input('role'))->first();
        $clanmembership->role()->associate($role);
        $clanmembership->save();
        return response()->redirectToRoute('clans.show', $clan->code)->with('successMessage', "{$clanmembership->user->nickname}'s role has been updated");
    }

    public function delete(Clan $clan, ClanMembership $clanmembership)
    {
        return view('clans.members.delete', [
            'clan' => $clan,
            'member' => $clanmembership,
            'leave' => $clanmembership->user_id === Auth::user()->id,
        ]);
    }

    public function destroy(Clan $clan, ClanMembership $clanmembership)
    {
        $message = "{$clanmembership->user->nickname} has been removed";
        $location = route('clans.show', $clan->code);
        if ($clanmembership->user_id === Auth::user()->id) {
            $message = "You have left the clan";
            $location = route('clans.index');
        }
        $clanmembership->delete();
        return response()->redirectTo($location)->with('successMessage', $message);
    }
}
