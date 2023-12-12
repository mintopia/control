@extends('layouts.app', [
    'activenav' => 'clans',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('clans.index') }}">Clans</a></li>
    <li class="breadcrumb-item"><a href="{{ route('clans.show', $clan->code) }}">{{ $clan->name }}</a></li>
    <li class="breadcrumb-item active">
        <a href="{{ route('clans.members.delete', [$clan->code, $member->id]) }}">Edit {{ $member->user->nickname }}</a>
    </li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Edit {{ $member->user->nickname }}</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('clans.members.update', [$clan->code, $member->id]) }}" method="post" class="card">
            {{ csrf_field() }}
            {{ method_field('PATCH') }}
            <div class="card-body">
                <div class="mb-3">
                    <h3 class="card-title">Role</h3>
                    <p class="card-subtitle">
                        A member's role determines what permissions they have in the clan.
                    </p>
                    @if (Auth::user()->id === $member->user_id)
                        <div class="alert alert-warning">
                            <div class="d-flex">
                                <div class="p-2">
                                    <i class="icon ti ti-alert-triangle"></i>
                                </div>
                                <div>
                                    You are editing permissions for yourself. If you change your membership from Leader, you will be unable to manage
                                    this clan.
                                </div>
                            </div>
                        </div>
                    @endif
                    <div>
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="role" value="leader" @if(old('role', $member->role->code) === 'leader') checked="" @endif>
                            <span class="form-check-label">Leader</span>
                            <p class="form-hint">
                                The user will have full control of the clan.
                            </p>
                        </label>
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="role" value="seatmanager" @if(old('role', $member->role->code) === 'seatmanager') checked="" @endif>
                            <span class="form-check-label">Seating Manager</span>
                            <p class="form-hint">
                                The user will be able to move people in the clan to different seats.
                            </p>
                        </label>
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="role" value="member" @if(old('role', $member->role->code) === 'member') checked="" @endif>
                            <span class="form-check-label">Member</span>
                            <p class="form-hint">
                                The user can see the clan, all members and the invite code.
                            </p>
                        </label>
                    </div>
                </div>
                @error('role')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('clans.show', $clan->code) }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection
