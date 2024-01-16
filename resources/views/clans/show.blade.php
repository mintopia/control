@extends('layouts.app', [
    'activenav' => 'clans',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('clans.index') }}">Clans</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('clans.show', $clan->code) }}">{{ $clan->name }}</a></li>
@endsection

@section('content')
    <div class="row g-2 align-items-center mb-4">
        <div class="col page-header mt-2">
            <h1>{{ $clan->name }}</h1>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                @can('update', $clan)
                    <a href="{{ route('clans.edit', $clan->code) }}" class="btn btn-outline-primary d-inline-block">
                        <i class="icon ti ti-edit"></i>
                        Edit
                    </a>
                    <a href="{{ route('clans.delete', $clan->code) }}" class="btn btn-danger d-inline-block">
                        <i class="icon ti ti-trash"></i>
                        Delete
                    </a>
                @endcan
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Name',
                                    'field' => 'name',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Role',
                                    'field' => 'role',
                                ])
                            </th>
                            <th class="w-2"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td>
                                    <div class="d-flex py-1 align-items-center">
                                        <span class="avatar me-2"
                                              style="background-image: url('{{ $member->user->avatarUrl() }}')"></span>
                                        <div class="flex-fill">
                                            <div class="font-weight-medium">
                                                {{ $member->user->nickname }}
                                                @if($member->user_id === Auth::user()->id)
                                                    <i class="icon ti ti-star-filled text-yellow" title="You!"></i>
                                                @endif
                                                <br/>
                                                <span class="text-muted">{{ $member->user->name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @can('update', $member)
                                        <a href="{{ route('clans.members.edit', [$clan->code, $member->id]) }}">{{ $member->role->name }}</a>
                                    @else
                                        {{ $member->role->name }}
                                    @endcan
                                </td>
                                <td class="text-end">
                                    @can('delete', $member)
                                        <a href="{{ route('clans.members.delete', [$clan->code, $member->id]) }}"
                                           class="btn btn-danger">
                                            @if($member->user_id === Auth::user()->id)
                                                Leave
                                            @else
                                                Remove
                                            @endif
                                        </a>
                                    @endcan
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="3" class="text-center p-4">
                                    <p>There are no members</p>
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
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Clan Invite Code</h3>
                </div>
                <div class="card-body">
                    <p>
                        Invite people to join this clan by sharing the invite code.
                    </p>
                    <h2 class="text-center">
                        <strong class="user-select-all">{{ $clan->invite_code }}</strong>
                    </h2>
                </div>
                @can('update', $clan)
                    <div class="card-footer">
                        <form class="d-flex" action="{{ route('clans.regenerate', $clan->code) }}" method="post">
                            {{ csrf_field() }}
                            <button type="submit" class="btn btn-primary-outline ms-auto">
                                <i class="icon ti ti-refresh"></i>
                                Generate New Code
                            </button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection
