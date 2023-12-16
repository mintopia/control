@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.seats._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.events.seatingplans.seats.edit', [$event->code, $plan->id, $seat->id]) }}">Edit</a>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Edit {{ $seat->label }}</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('admin.events.seatingplans.seats.update', [$event->code, $plan->id, $seat->id]) }}" method="post" class="card">
            {{ csrf_field() }}
            {{ method_field('PATCH') }}
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
