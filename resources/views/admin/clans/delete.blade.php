@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.clans._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.clans.delete', $clan->code) }}">Delete</a>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Delete {{ $clan->name }}</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('admin.clans.destroy', $clan->code) }}" method="post" class="card">
            <div class="card-status-top bg-danger"></div>
            {{ csrf_field() }}
            {{ method_field('DELETE') }}
            <div class="card-body text-center">
                <i class="icon mb-4 ti ti-alert-triangle icon-lg text-danger"></i>
                <p class="mt-4">
                    Are you sure you want to delete <strong>{{ $clan->name }}</strong>?
                </p>
                <p>
                    To continue, please type 'delete' in the box below.
                </p>
                <div class="mb-3 col-md-6 offset-md-3">
                    <div>
                        <input type="text" name="confirm" class="form-control @error('confirm') is-invalid @enderror" placeholder="delete">
                        @error('confirm')
                        <p class="invalid-feedback">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.clans.show', $clan->code) }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-danger ms-auto">Delete</button>
                </div>
            </div>
        </form>
    </div>
@endsection
