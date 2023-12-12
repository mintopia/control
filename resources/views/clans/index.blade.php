@extends('layouts.app', [
    'activenav' => 'clans',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('clans.index') }}">Clans</a></li>
@endsection

@section('content')
    <div class="row g-2 align-items-center mb-4">
        <div class="col page-header mt-2">
            <h1>Clans</h1>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="{{ route('clans.create') }}" class="btn btn-primary d-inline-block">
                    <i class="icon ti ti-plus"></i>
                    Create Clan
                </a>
            </div>
        </div>
    </div>

    <p>
        Creating and joining clans makes it easy to arrange your seats. The clan leader can appoint a seating manager, who is
        able to pick your seat for you.
    </p>

    <div class="row mb-2">
        <div class="col-md-7 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Your Clans</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th class="w-1">Members</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td>
                                    <a href="{{ route('clans.show', $member->clan->code) }}">{{ $member->clan->name }}</a>
                                </td>
                                <td>{{ $member->role->name }}</td>
                                <td>{{ count($member->clan->members) }}</td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="3" class="text-center p-4">
                                    <p>You aren't in any clans</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials._pagination', [
                    'page' => $members
                ])
            </div>
        </div>
        <div class="col-md-5 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Join Clan</h3>
                </div>
                <form class="card-body" action="{{ route('clans.members.store') }}" method="post">
                    {{ csrf_field() }}
                    <p>
                        To join a clan, enter the invite code.
                    </p>

                    <div class="mb-3">
                        <input type="text" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" name="code" id="code">
                        @error('code')
                            <p class="invalid-feedback">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary w-100">
                            Join Clan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
