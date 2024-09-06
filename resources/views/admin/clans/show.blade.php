@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.clans._breadcrumbs', [
        'active' => true,
    ])
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>{{ $clan->name }}</h1>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">ID</div>
                            <div class="datagrid-content">{{ $clan->id }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Name</div>
                            <div class="datagrid-content">{{ $clan->name }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Code</div>
                            <div class="datagrid-content">{{ $clan->code }}</div>
                        </div>
                        @can('admin')
                            <div class="datagrid-item">
                                <div class="datagrid-title">Invite Code</div>
                                <div class="datagrid-content"><span class="user-select-all">{{ $clan->invite_code }}</span>
                                </div>
                            </div>
                        @endcan
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">
                                <span title="{{ $clan->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $clan->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @can('admin')
                    <div class="card-footer align-content-end d-flex btn-list">
                        <a href="{{ route('admin.clans.delete', $clan->code) }}" class="btn btn-outline-danger">
                            <i class="icon ti ti-trash"></i>
                            Delete
                        </a>
                        <a href="{{ route('admin.clans.regenerate', $clan->code) }}"
                           class="btn btn-primary-outline ms-auto">
                            <i class="icon ti ti-refresh"></i>
                            Generate New Invite Code
                        </a>
                        <a href="{{ route('admin.clans.edit', $clan->code) }}" class="btn btn-primary">
                            <i class="icon ti ti-edit"></i>
                            Edit
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    <h2>Members</h2>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'ID',
                                    'field' => 'id',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'User',
                                    'field' => 'user',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Role',
                                    'field' => 'role',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Joined',
                                    'field' => 'created',
                                ])
                            </th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td class="text-muted">{{ $member->user->id }}</td>
                                <td>
                                    <a href="{{ route('admin.users.show', $member->user->id) }}">{{ $member->user->nickname }}</a>
                                </td>
                                <td>{{ $member->role->name }}</td>
                                <td>
                                    <span title="{{ $member->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $member->created_at->diffForHumans() }}
                                    </span>
                                </td>
                                @can('admin')
                                    <td class="btn-list">
                                        <a class="btn btn-outline-primary ms-auto"
                                           href="{{ route('admin.clans.members.edit', [$clan->code, $member->id]) }}">
                                            <i class="icon ti ti-edit"></i>
                                            Edit
                                        </a>
                                        <a class="btn btn-outline-danger @if(!$member->canDelete()) disabled @endif"
                                           @if ($member->canDelete()) href="{{ route('admin.clans.members.delete', [$clan->code, $member->id]) }}" @endif>
                                            <i class="icon ti ti-trash"></i>
                                            Remove
                                        </a>
                                    </td>
                                @endcan
                            </tr>

                        @empty
                            <tr>
                                <td colspan="5" class="text-center p-4">
                                    <p>There are no members in this clan</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
