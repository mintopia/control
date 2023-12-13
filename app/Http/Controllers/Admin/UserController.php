<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = (object)[];
        $query = User::query();

        if ($request->input('id')) {
            $filters->id = $request->input('id');
            $query = $query->whereId($filters->id);
        }

        if ($request->input('name')) {
            $filters->name = $request->input('name');
            $query = $query->where('name', 'LIKE', "%{$filters->name}%");
        }

        if ($request->input('nickname')) {
            $filters->nickname = $request->input('nickname');
            $query = $query->where('nickname', 'LIKE', "%{$filters->nickname}%");
        }

        if ($request->input('email')) {
            $filters->email = $request->input('email');
            $query = $query->whereHas('emails', function ($query) use ($filters) {
                $query->where('email', 'LIKE', "%{$filters->email}%");
            });
        }

        $params = (array)$filters;

        switch ($request->input('order')) {
            case 'nickname':
            case 'name':
            case 'created_at':
                $params['order'] = $request->input('order');
                break;
            case 'id':
            default:
                $params['order'] = 'id';
                break;
        };

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

        $users = $query->with('primaryEmail')->paginate($params['perPage'])->appends($params);
        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'params' => $params,
        ]);
    }

    public function show(User $user)
    {
        return view('admin.users.show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::all(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->updateObject($user, $request);
        return response()->redirectToRoute('users.show', $user->id)->with('successMessage', 'The user has been updated');
    }

    public function delete(User $user)
    {
        return view('admin.users.delete', [
            'user' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->redirectToRoute('admin.users.index')->with('sucessMessage', 'The user has been deleted');
    }

    protected function updateObject(User $user, Request $request)
    {
        $user->nickname = $request->input('nickname');
        $user->name = $request->input('name');
        $user->save();
    }

    public function impersonate(Request $request, User $user)
    {
        $originalUser = $request->user();
        $request->session()->flush();
        $request->session()->regenerate(true);
        Auth::login($user);
        $request->session()->put('originalUserId', $originalUser->id);
        $request->session()->put('impersonating', true);
        return response()->redirectToRoute('home');
    }
}
