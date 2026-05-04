@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.apikeys._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.settings.apikeys.create') }}">New API Key</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>New API Key</h1>
    </div>

    <div class="col-md-8 offset-md-2">
        <form action="{{ route('admin.settings.apikeys.store') }}" method="post" class="card">
            {{ csrf_field() }}
            @include('admin.apikeys._form')
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.settings.apikeys.index') }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Create</button>
                </div>
            </div>
        </form>
    </div>
@endsection
