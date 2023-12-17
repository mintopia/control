@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.tickettypes._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.events.tickettypes.mappings.edit', [$event->code, $type->id, $mapping->id]) }}">Edit Mapping</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Edit Ticket Type Mapping</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('admin.events.tickettypes.mappings.update', [$event->code, $type->id, $mapping->id]) }}" method="post" class="card">
            {{ csrf_field() }}
            {{ method_field('PATCH') }}
            @include('admin.tickettypemappings._form')
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.events.tickettypes.show', [$event->code, $type->id]) }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection
