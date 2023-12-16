@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.events._breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Edit</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Edit {{ $event->name }}</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('admin.events.update', $event->code) }}" method="post" class="card">
            {{ csrf_field() }}
            {{ method_field('PATCH') }}
            @include('admin.events._form')
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.events.show', $event->code) }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection
