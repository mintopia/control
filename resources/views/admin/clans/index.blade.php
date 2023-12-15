@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.clans.index') }}">Clans</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Clans</h1>
    </div>

    <div class="row">
        <div class="col-md-12 col-lg-3 mb-4">
            <form action="{{ route('admin.clans.index') }}" method="get" class="card">
                <div class="card-header">
                    <h3 class="card-title">Search</h3>
                </div>
                <div class="card-body">
                    @include('partials._searchtextfield', [
                        'name' => 'ID',
                        'property' => 'id',
                    ])
                    @include('partials._searchtextfield', [
                        'name' => 'Name',
                        'property' => 'nickname',
                    ])
                    @include('partials._searchtextfield', [
                        'name' => 'Code',
                        'property' => 'code',
                    ])
                    @include('partials._searchtextfield', [
                        'name' => 'Invite Code',
                        'property' => 'invite_code',
                    ])

                </div>
                <div class="card-footer d-flex">
                    <button class="btn btn-primary ms-auto" type="submit">Search</button>
                </div>
            </form>
        </div>

        <div class="col-lg-9 col-md-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover table-outline table-vcenter text-nowrap card-table">
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
                                    'title' => 'Name',
                                    'field' => 'name',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Code',
                                    'field' => 'code',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Members',
                                    'field' => 'members_count',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Invitation Code',
                                    'field' => 'invite_code',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Created',
                                    'field' => 'created_at',
                                    'direction' => 'desc',
                                ])
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($clans as $clan)
                                <tr>
                                    <td class="text-muted">{{ $clan->id }}</td>
                                    <td>
                                        <a href="{{ route('admin.clans.show', $clan->code) }}">
                                            {{ $clan->name }}
                                        </a>
                                    </td>
                                    <td>{{ $clan->code }}</td>
                                    <td>{{ $clan->members_count }}</td>
                                    <td><span class="user-select-all">{{ $clan->invite_code }}</span></td>
                                    <td>
                                        <span title="{{ $clan->created_at->format('Y-m-d H:i:s') }}">
                                            {{ $clan->created_at->diffForHumans() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('partials._pagination', [
                    'page' => $clans
                ])
            </div>
        </div>
    </div>
@endsection
