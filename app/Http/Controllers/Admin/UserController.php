<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
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
            'currentRoles' => $user->roles()->pluck('code')->toArray(),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $this->updateObject($user, $request);
        return response()->redirectToRoute('admin.users.show', $user->id)->with('successMessage', 'The user has been updated');
    }

    public function delete(User $user)
    {
        return view('admin.users.delete', [
            'user' => $user,
        ]);
    }

    public function destroy(DeleteRequest $request, User $user)
    {
        $plans = [];
        foreach ($user->tickets()->with('seat', 'seat.plan')->get() as $ticket) {
            if ($ticket->seat) {
                $plans[] = $ticket->seat->plan;
            }
        }
        $plans = array_unique($plans);
        $user->delete();
        foreach ($plans as $plan) {
            $plan->updateRevision();
        }
        return response()->redirectToRoute('admin.users.index')->with('successMessage', 'The user has been deleted');
    }

    protected function updateObject(User $user, Request $request)
    {
        $user->nickname = $request->input('nickname');
        $user->name = $request->input('name');
        $user->primaryEmail()->associate($user->emails()->find($request->input('primary_email_id')));

        if ($request->input('terms', false)) {
            if ($user->terms_agreed_at === null) {
                $user->terms_agreed_at = Carbon::now();
            }
        } else {
            $user->terms_agreed_at = null;
        }

        $user->first_login = !$request->input('first_login', false);
        $user->suspended = (bool)$request->input('suspended', false);

        $wantedRoles = $request->input('roles', []);
        $hasRoles = [];
        foreach ($user->roles as $role) {
            if (!in_array($role->code, $wantedRoles)) {
                $user->roles()->detach($role);
                continue;
            }
            $hasRoles[] = $role->code;
        }
        foreach ($wantedRoles as $role) {
            if (!in_array($role, $hasRoles)) {
                $role = Role::whereCode($role)->first();
                $user->roles()->attach($role);
            }
        }

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

    public function sync_tickets(User $user)
    {
        $user->syncTickets(force: true);
        return response()->redirectToRoute('admin.users.show', $user->id)->with('successMessage', "Tickets will be synchronised for {$user->nickname}");
    }
}
