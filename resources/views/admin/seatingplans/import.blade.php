@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.seatingplans._breadcrumbs')
    <li class="breadcrumb-item active"><a
            href="{{ route('admin.events.seatingplans.import', [$event->code, $plan->id]) }}">Import Seats</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Import Seats</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('admin.events.seatingplans.import', [$event->code, $plan->id]) }}" method="post"
              class="card" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="card-body">
                <p>
                    This page allows you to import a CSV containing seat information.
                </p>
                <p>
                    The format of the CSV is identical to the output format from exporting seats. The first row is an
                    optional header row. If it is detected, it will be ignored.
                </p>
                <p>An example format would be:</p>
                <pre><code>ID,X,Y,Row,Number,Label,Description,CSS Class,Disabled
1,2,22,A,1,A1,,,0
2,3,22,A,2,A2,,,0
3,4,22,A,3,A3,,,0
4,5,22,A,4,A4,,,0</code></pre>
                <p>
                    If the ID field is included for a seat, then an existing seat with that ID in the plan will be
                    updated based on the data in the CSV. If the ID field is empty, or a seat with that ID can't be
                    found, a new seat will be added with that ID.
                </p>
                <p>
                    Finally, if you want to remove all seats from the plan before adding them, select the 'Remove all
                    existing seats' option.
                </p>
                <div class="mb-3">
                    <label class="form-label">CSV File</label>
                    <div>
                        <input type="file" name="csv" class="form-control @error('csv') is-invalid @enderror"
                               accept=".csv,text/csv">
                        <small class="form-hint">The CSV file containing seats to import</small>
                        @error('csv')
                        <p class="invalid-feedback">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" name="wipe" value="1"
                               @if(old('wipe')) checked @endif>
                        Remove all existing seats
                    </label>
                </div>
            </div>

            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.events.seatingplans.show', [$event->code, $plan->id]) }}"
                       class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection
