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
        <form action="{{ route('admin.tickets.import.process') }}" method="post" class="card">
            {{ csrf_field() }}
            <div class="card-body">
                <p>
                    The tickets you are going to import are:
                </p>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                    <tr>
                        <th>Event</th>
                        <th>Ticket Type</th>
                        <th>User</th>
                        <th>Seat</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($imports as $import)
                        <tr>
                            <td>{{ $import->event->name }}</td>
                            <td>{{ $import->type->name }}</td>
                            <td>{{ $import->user->nickname }}</td>
                            <td>
                                @if($import->seat)
                                    {{ $import->seat->label }}
                                @else
                                    <span class="None"></span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('admin.tickets.index') }}"
                       class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Import Tickets</button>
                </div>
            </div>
        </form>
    </div>
@endsection
