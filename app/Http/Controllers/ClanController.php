<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClanRequest;
use App\Models\Clan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClanController extends Controller
{
    public function index(Request $request)
    {
        $clans = $request->user()
            ->clanMemberships()
            ->join('clans', 'clan_memberships.clan_id', '=', 'clans.id')
            ->select('clan_memberships.*')
            ->orderBy('clans.name', 'asc')
            ->with(['clan', 'clan.members', 'role'])
            ->paginate();
        return view('clans.index', [
            'members' => $clans,
        ]);
    }

    public function create()
    {
        return view('clans.create');
    }

    public function store(ClanRequest $request)
    {
        $clan = new Clan();
        DB::transaction(function () use ($request, $clan) {
            $this->updateObject($clan, $request);
            $clan->addUser($request->user(), 'leader');
        });
        return response()->redirectToRoute('clans.show', $clan->code)->with('successMessage', 'The clan has been created');
    }

    protected function updateObject(Clan $clan, ClanRequest $request)
    {
        $clan->name = $request->input('name');
        $clan->save();
    }

    public function show(Request $request, Clan $clan)
    {
        $query = $clan->members();
        $params['order_direction'] = $request->input('order_direction', 'asc');
        if (!in_array($params['order_direction'], ['asc', 'desc'])) {
            $params['order_direction'] = 'asc';
        }

        switch ($request->input('order')) {
            case 'name':
                $params['order'] = 'name';
                $query
                    ->join('users', 'clan_memberships.user_id', '=', 'users.id')
                    ->orderBy('users.nickname', $params['order_direction']);
                break;

            case 'role':
            default:
                $params['order'] = 'role';
                $query
                    ->join('clan_roles', 'clan_memberships.clan_role_id', '=', 'clan_roles.id')
                    ->join('users', 'clan_memberships.user_id', '=', 'users.id')
                    ->orderBy('clan_roles.id', $params['order_direction'])
                    ->orderBy('users.nickname', $params['order_direction']);
                break;
        }

        $members = $query
            ->with(['user', 'role'])
            ->select('clan_memberships.*')
            ->paginate()
            ->appends($params);

        return view('clans.show', [
            'clan' => $clan,
            'members' => $members,
            'params' => $params,
        ]);
    }

    public function edit(Clan $clan)
    {
        return view('clans.edit', [
            'clan' => $clan,
        ]);
    }

    public function update(ClanRequest $request, Clan $clan)
    {
        $this->updateObject($clan, $request);
        return response()->redirectToRoute('clans.show', $clan->code)->with('successMessage', 'The clan has been updated');
    }

    public function regenerate(Clan $clan)
    {
        $clan->generateCode();
        $clan->save();
        return response()->redirectToRoute('clans.show', $clan->code)->with('successMessage', 'The code has been regenerated');
    }

    public function destroy(Clan $clan)
    {
        $clan->delete();
        return response()->redirectToRoute('clans.index')->with('successMessage', 'The clan has been deleted');
    }

    public function delete(Clan $clan)
    {
        return view('clans.delete', [
            'clan' => $clan,
        ]);
    }
}
