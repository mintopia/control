@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.users.index') }}">Users</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Users</h1>
    </div>

    <div class="row">
        <div class="col-md-12 col-lg-3 mb-4">
            <form action="{{ route('admin.users.index') }}" method="get" class="card">
                <div class="card-header">
                    <h3 class="card-title">Search</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label class="form-label" for="id">ID</label>
                        <input class="form-control @error('id') is-invalid @enderror" type="text" name="id" placeholder="ID" value="{{ $filters->id ?? '' }}" />
                        @error('id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="nickname">Nickname</label>
                        <input class="form-control @error('nickname') is-invalid @enderror" type="text" name="nickname" placeholder="Nickname" value="{{ $filters->nickname ?? '' }}" />
                        @error('nickname')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="name">Name</label>
                        <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" placeholder="Name" value="{{ $filters->name ?? '' }}" />
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control @error('email') is-invalid @enderror" type="text" name="email" placeholder="email" value="{{ $filters->email ?? '' }}" />
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

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
                                    'title' => 'Nickname',
                                    'field' => 'nickname',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Name',
                                    'field' => 'name',
                                ])
                            </th>
                            <th>Primary Email</th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Created',
                                    'field' => 'created_at',
                                ])
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td class="text-muted">{{ $user->id }}</td>
                                    <td>
                                        <a href="{{ route('admin.users.show', $user->id) }}">
                                            {{ $user->nickname }}
                                        </a>
                                    </td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->primaryEmail->email ?? 'None' }}</td>
                                    <td>
                                        <span title="{{ $user->created_at->format('Y-m-d H:i:s') }}">
                                            {{ $user->created_at->diffForHumans() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('partials._pagination', [
                    'page' => $users
                ])
            </div>
        </div>
    </div>
@endsection
