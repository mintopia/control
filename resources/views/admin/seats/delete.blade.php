@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.seats._breadcrumbs')
    <li class="breadcrumb-item active"><a
            href="{{ route('admin.events.seatingplans.seats.delete', [$event->code, $plan->id, $seat->id]) }}">Delete</a>
        @endsection

        @section('content')
            <div class="page-header mt-0">
                <h1>Delete {{ $seat->label }}</h1>
            </div>

            <div class="col-md-6 offset-md-3">
                <form
                    action="{{ route('admin.events.seatingplans.seats.destroy', [$event->code, $plan->id, $seat->id]) }}"
                    method="post" class="card">
                    <div class="card-status-top bg-danger"></div>
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <div class="card-body text-center">
                        <i class="icon mb-4 ti ti-alert-triangle icon-lg text-danger"></i>
                        <p class="mt-4">
                            Are you sure you want to delete <strong>{{ $seat->label }}</strong> for {{ $plan->name }}?
                        </p>
                    </div>
                    <div class="card-footer text-end">
                        <div class="d-flex">
                            <a href="{{ route('admin.events.seatingplans.show', [$event->code, $plan->id]) }}"
                               class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-danger ms-auto">Delete</button>
                        </div>
                    </div>
                </form>
            </div>
@endsection
