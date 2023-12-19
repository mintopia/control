@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.clans._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.clans.members.edit', [$clan->code, $member->id]) }}">Edit
            {{ $member->user->nickname }}</a>
        @endsection

        @section('content')
            <div class="page-header mt-0">
                <h1>Edit {{ $member->user->nickname }}</h1>
            </div>

            <div class="col-md-6 offset-md-3">
                <form action="{{ route('admin.clans.members.update', [$clan->code, $member->id]) }}" method="post"
                      class="card">
                    {{ csrf_field() }}
                    {{ method_field('PATCH') }}
                    <div class="card-body">
                        <div>
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="role" value="leader"
                                       @if(old('role', $member->role->code) === 'leader') checked="" @endif>
                                <span class="form-check-label">Leader</span>
                                <p class="form-hint">
                                    The user will have full control of the clan.
                                </p>
                            </label>
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="role" value="seatmanager"
                                       @if(old('role', $member->role->code) === 'seatmanager') checked="" @endif>
                                <span class="form-check-label">Seating Manager</span>
                                <p class="form-hint">
                                    The user will be able to move people in the clan to different seats.
                                </p>
                            </label>
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="role" value="member"
                                       @if(old('role', $member->role->code) === 'member') checked="" @endif>
                                <span class="form-check-label">Member</span>
                                <p class="form-hint">
                                    The user can see the clan, all members and the invite code.
                                </p>
                            </label>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <div class="d-flex">
                            <a href="{{ route('admin.clans.show', $clan->code) }}" class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-primary ms-auto">Save</button>
                        </div>
                    </div>

                </form>
            </div>
@endsection
