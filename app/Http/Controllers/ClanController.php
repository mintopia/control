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
            ->with(['clan' => function ($query) {
                $query->orderBy('name', 'ASC');
            }, 'clan.members', 'role'])
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

    public function show(Clan $clan)
    {
        $members = $clan->members()->with([
            'user' => function ($query) {
                $query->orderBy('nickname', 'ASC');
            },
            'role',
        ])->paginate();
        return view('clans.show', [
            'clan' => $clan,
            'members' => $members,
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
