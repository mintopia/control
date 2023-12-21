@extends('layouts.app', [
    'activenav' => 'tickets',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('tickets.index') }}">Tickets</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Tickets</h1>
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
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Seat</th>
                            <th>Event</th>
                            <th class="w-1"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($tickets as $ticket)
                            <tr>
                                <td>
                                    <a href="{{ route('tickets.show', $ticket->id) }}">
                                        {{ $ticket->reference }}
                                    </a>
                                </td>
                                <td>{{ $ticket->type->name }}</td>
                                <td>
                                    @if($ticket->seat)
                                        <a href="{{ route('seatingplans.show', $ticket->event->code) }}#{{ $ticket->seat->label }}">{{ $ticket->seat->label }}</a>
                                    @elseif($ticket->canPickSeat())
                                        None - <a href="{{ route('seatingplans.show', $ticket->event->code) }}">Choose
                                            Seat</a>
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $ticket->event->name }}
                                    @if($ticket->event->draft)
                                        <span class="badge bg-muted text-muted-fg">Draft</span>
                                    @endif
                                </td>
                                <td></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center p-4">
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
                <form class="card-body" action="{{ route('tickets.transfer') }}" method="post">
                    {{ csrf_field() }}
                    <p>
                        If you have been given a ticket transfer code, enter it here to transfer the ticket to your
                        account.
                    </p>

                    <div class="mb-3">
                        <input type="text" class="form-control @error('code') is-invalid @enderror"
                               value="{{ old('code') }}" name="code" id="code">
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
