<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClanMembershipUpdateRequest;
use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\ClanRole;

class ClanMembershipController extends Controller
{
    public function edit(Clan $clan, ClanMembership $member)
    {
        return view('admin.clanmemberships.edit', [
            'clan' => $clan,
            'member' => $member,
        ]);
    }

    public function update(ClanMembershipUpdateRequest $request, Clan $clan, ClanMembership $member)
    {
        $role = ClanRole::whereCode($request->input('role'))->first();
        $member->role()->associate($role);
        $member->save();
        return response()->redirectToRoute('admin.clans.show', $clan->code)->with('successMessage', 'The clan member has been updated');
    }

    public function destroy(Clan $clan, ClanMembership $member)
    {
        if (!$member->canDelete()) {
            return response()->redirectToRoute('admin.clans.show', $clan->code)->with('errorMessage', 'It is not possible to remove this clan member');
        }
        $member->delete();
        return response()->redirectToRoute('admin.clans.show', $clan->code)->with('successMessage', 'The clan member has been removed');
    }

    public function delete(Clan $clan, ClanMembership $member)
    {
        if (!$member->canDelete()) {
            return response()->redirectToRoute('admin.clans.show', $clan->code)->with('errorMessage', 'It is not possible to remove this clan member');
        }
        return view('admin.clanmemberships.delete', [
            'clan' => $clan,
            'member' => $member,
        ]);
    }
}
