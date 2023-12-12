@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.users.show', $user->id) }}">{{ $user->nickname }}</a></li>
@endsection

@section('content')
    <div class="page-header">
        <h1>{{ $user->nickname }}</h1>
    </div>

@endsection
