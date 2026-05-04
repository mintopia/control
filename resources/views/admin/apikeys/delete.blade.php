@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.apikeys._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.settings.apikeys.delete', $apikey->id) }}">Delete</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Delete {{ $apikey->name }}</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('admin.settings.apikeys.destroy', $apikey->id) }}" method="post" class="card">
            <div class="card-status-top bg-danger"></div>
            {{ csrf_field() }}
            {{ method_field('DELETE') }}
            <input type="hidden" name="confirm" value="delete">
            <div class="card-body text-center">
                <i class="icon mb-4 ti ti-alert-triangle icon-lg text-danger"></i>
                <p class="mt-4">
                    Are you sure you want to delete <strong>{{ $apikey->name }}</strong> (<code>ctrl_…{{ $apikey->last_four }}</code>)?
                </p>
                <p class="text-muted">Any integration using this key will start receiving 401 responses immediately.</p>
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.settings.apikeys.index') }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-danger ms-auto">Delete</button>
                </div>
            </div>
        </form>
    </div>
@endsection
