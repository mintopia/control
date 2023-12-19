@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Tickets</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.tickets.create') }}">Add Ticket</a>
        @endsection

        @section('content')
            <div class="page-header mt-0">
                <h1>Add Ticket</h1>
            </div>

            <div class="col-md-6 offset-md-3">
                <form action="{{ route('admin.tickets.store') }}" method="post" class="card">
                    {{ csrf_field() }}
                    @include('admin.tickets._form')
                    <div class="card-footer text-end">
                        <div class="d-flex">
                            <a href="{{ route('admin.tickets.index') }}" class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-primary ms-auto">Save</button>
                        </div>
                    </div>
                </form>
            </div>
@endsection
