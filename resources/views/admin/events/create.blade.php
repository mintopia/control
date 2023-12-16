@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.events.index') }}">Events</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.events.create') }}">Create Event</a>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Create Event</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('admin.events.store') }}" method="post" class="card">
            {{ csrf_field() }}
            @include('admin.events._form')
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.events.index') }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection
