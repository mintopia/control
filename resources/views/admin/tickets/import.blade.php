@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Tickets</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.tickets.import') }}">Import</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Import Tickets</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('admin.tickets.import.show') }}" method="post"
              class="card" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="card-body">
                <p>
                    This page allows you to import a CSV of tickets.
                </p>
                <p>
                    You will need to specify the Ticket Type ID, a User ID and an optional Seat Label
                </p>
                <p>An example format would be:</p>
                <pre><code>Ticket Type ID,User ID,Seat Label
1,1,A1
1,42,A4
1,2,
2,1,</code></pre>
                <p>
                    You can find user IDs from the Users page, and Ticket Type ID from the Event admin page.
                </p>
                <p>
                    You will be shown a preview of the import and given a chance to confirm it on the next page.
                </p>
                <div class="mb-3">
                    <label class="form-label">CSV File</label>
                    <div>
                        <input type="file" name="csv" class="form-control @error('csv') is-invalid @enderror"
                               accept=".csv,text/csv">
                        <small class="form-hint">The CSV file containing tickets to import</small>
                        @error('csv')
                        <p class="invalid-feedback">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.tickets.index') }}"
                       class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Preview</button>
                </div>
            </div>
        </form>
    </div>
@endsection
