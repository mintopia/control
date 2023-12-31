<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\ClanRequest;
use App\Models\Clan;
use Illuminate\Http\Request;

class ClanController extends Controller
{
    public function index(Request $request)
    {

        $filters = (object)[];
        $query = Clan::query();

        if ($request->input('id')) {
            $filters->id = $request->input('id');
            $query = $query->whereId($filters->id);
        }

        if ($request->input('name')) {
            $filters->name = $request->input('name');
            $query = $query->where('name', 'LIKE', "%{$filters->name}%");
        }

        if ($request->input('code')) {
            $filters->code = $request->input('code');
            $query = $query->where('code', 'LIKE', "%{$filters->code}%");
        }

        if ($request->input('invite_code')) {
            $filters->invite_code = $request->input('invite_code');
            $query = $query->where('invite_code', 'LIKE', "%{$filters->invite_code}%");
        }

        $params = (array)$filters;

        switch ($request->input('order')) {
            case 'name':
            case 'code':
            case 'created_at':
            case 'members_count':
            case 'invite_code':
                $params['order'] = $request->input('order');
                break;
            case 'id':
            default:
                $params['order'] = 'id';
                break;
        }

        switch ($request->input('order_direction', 'asc')) {
            case 'desc':
                $params['order_direction'] = 'desc';
                break;
            case 'asc':
            default:
                $params['order_direction'] = 'asc';
        }

        $query = $query->orderBy($params['order'], $params['order_direction']);

        $params['page'] = $request->input('page', 1);
        $params['perPage'] = $request->input('perPage', 20);

        $clans = $query->withCount('members')->paginate($params['perPage'])->appends($params);
        return view('admin.clans.index', [
            'clans' => $clans,
            'filters' => $filters,
            'params' => $params,
        ]);
    }

    public function show(Request $request, Clan $clan)
    {
        $query = $clan->members();

        $orderDirection = 'asc';
        $params = [];
        if (in_array($request->input('order_direction', 'asc'), ['asc', 'desc'])) {
            $orderDirection = $request->input('order_direction', 'asc');
            $params['order_direction'] = $orderDirection;
        }

        switch ($request->input('order')) {
            case 'user':
                $params['order'] = 'user';
                $query = $query
                    ->join('users', 'clan_memberships.user_id', '=', 'users.id')
                    ->orderBy('users.nickname', $orderDirection);
                break;

            case 'role':
                $params['order'] = 'role';
                $query = $query->orderBy('clan_role_id', $orderDirection);
                break;

            case 'created':
                $params['order'] = 'created';
                $query = $query->orderBy('created_at', $orderDirection);
                break;

            case 'id':
            default:
                $params['order'] = 'id';
                $query = $query->orderBy('id', $orderDirection);
                break;
        }

        $members = $query
            ->with(['role', 'user'])
            ->select('clan_memberships.*')
            ->get();

        return view('admin.clans.show', [
            'clan' => $clan,
            'members' => $members,
            'params' => $params,
        ]);
    }

    public function edit(Clan $clan)
    {
        return view('admin.clans.edit', [
            'clan' => $clan,
        ]);
    }

    public function update(ClanRequest $request, Clan $clan)
    {
        $clan->name = $request->input('name');
        $clan->save();
        return response()->redirectToRoute('admin.clans.show', $clan->code)->with('successMessage', 'The clan has been updated');
    }

    public function destroy(DeleteRequest $request, Clan $clan)
    {
        $clan->delete();
        return response()->redirectToRoute('admin.clans.index')->with('successMessage', 'The clan has been deleted');
    }

    public function delete(Clan $clan)
    {
        return view('admin.clans.delete', [
            'clan' => $clan,
        ]);
    }

    public function regenerate(Clan $clan)
    {
        $clan->generateCode();
        $clan->save();
        return response()->redirectToRoute('admin.clans.show', $clan->code)->with('successMessage', 'The invite code has been regenerated');
    }
}
