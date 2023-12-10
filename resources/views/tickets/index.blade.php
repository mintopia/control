@extends('layouts.app', [
    'activenav' => 'tickets',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('tickets.index') }}">Tickets</a></li>
@endsection

@section('content')
    <div class="row g-2 align-items-center mb-4">
        <div class="col">
            <h2 class="page-title">Tickets</h2>
        </div>
    </div>

    <p>
        These are all the tickets associated with your email addresses or that have been transferred to you. If you are
        missing tickets, please check the email address has been added to your account and is verified.
    </p>

    <div class="row mb-2">
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Your Tickets</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Provider</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <td>{{ $ticket->external_id }}</td>
                                <td>{{ $ticket->provider->name }}</td>
                                <td></td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="3" class="text-center p-4">
                                    <p>You have no tickets</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @include('partials._pagination', [
                    'page' => $tickets
                ])
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Transfer Ticket</h3>
                </div>
                <form class="card-body" action="#" method="post">
                    {{ csrf_field() }}
                    <p>
                        If you have been given a ticket transfer code, enter it here to transfer the ticket to your account.
                    </p>

                    <div class="mb-3">
                        <input type="text" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" name="code" id="code">
                        @error('code')
                            <p class="invalid-feedback">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary w-100">
                            Transfer Ticket
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
