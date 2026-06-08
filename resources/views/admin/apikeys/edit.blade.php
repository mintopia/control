@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.apikeys._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.apikeys.edit', $apikey->id) }}">Edit</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Edit {{ $apikey->name }}</h1>
    </div>

    <div class="col-md-8 offset-md-2">
        <form action="{{ route('admin.apikeys.update', $apikey->id) }}" method="post" class="card">
            {{ csrf_field() }}
            {{ method_field('PATCH') }}
            @include('admin.apikeys._form')
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.apikeys.index') }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection
