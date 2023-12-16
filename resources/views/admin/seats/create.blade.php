@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.seatingplans._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.events.seatingplans.seats.create', [$event->code, $plan->id]) }}">Add Seat</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Add Seat</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('admin.events.seatingplans.seats.store', [$event->code, $plan->id]) }}" method="post" class="card">
            {{ csrf_field() }}
            @include('admin.seats._form')
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.events.seatingplans.show', [$event->code, $plan->id]) }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection
