@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.seatgroupassignments._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.events.seatgroups.assignments.create', [$event->code, $group->id]) }}">Add Seat Group</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Add Seat Group Assignment</h1>
    </div>
    <div class="col-md-6 offset-md-3">
        <form action="{{ route('admin.events.seatgroups.assignments.store', [$event->code, $group->id]) }}" method="post" class="card">
            {{ csrf_field() }}
            @include('admin.seatgroupassignments._form')
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.events.seatgroups.show', [$event->code, $group->id]) }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection
