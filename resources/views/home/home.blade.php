@extends('layouts.app', [
    'activenav' => 'home',
])

@section('breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('home') }}">Home</a></li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-9 col-sm-8 mb-4">
            <div class="page-title mt-0">
                <h1>Tickets</h1>
            </div>
            @if ($tickets)
                <div class="card mb-6">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Type</th>
                                <th>Event</th>
                                <th>Seat</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tickets as $ticket)
                                <tr>
                                    <td>
                                        <a href="{{ route('tickets.show', $ticket->id) }}">
                                            {{ $ticket->reference }}
                                        </a>
                                    </td>
                                    <td>{{ $ticket->type->name }}</td>
                                    <td>{{ $ticket->event->name }}</td>
                                    <td>
                                        <a href="{{ route('seatingplans.show', $ticket->event->code) }}">
                                            @if ($ticket->canPickSeat())
                                                {{ $ticket->seat->label ?? 'Choose Seat' }}
                                            @else
                                                {{ $ticket->seat->label ?? 'None' }}
                                            @endif
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($tickets->hasPages())
                        @include('partials._pagination', [
                        'page' => $tickets
                        ])
                    @endif
                </div>
            @else
                <p class="text-center">
                    <i class="icon ti ti-ticket-off icon-lg text-muted m-6"></i>
                </p>
                <p class="text-center">
                    You have no tickets for upcoming events.
                </p>
            @endif

            <div class="page-title mt-0">
                <h1>Events</h1>
            </div>
            @if($events)
                <div class="card mb-6">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Starts</th>
                                <th>Finishes</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($events as $event)
                                <tr>
                                    <td>
                                        <a href="{{ route('seatingplans.show', $event->code) }}">{{ $event->name }}</a>
                                    </td>
                                    <td>{{ $event->starts_at->format('gA jS F Y') }}</td>
                                    <td>{{ $event->ends_at->format('gA jS F Y') }}</td>
                                    <td class="align-content-end">
                                        @if($event->boxoffice_url)
                                            <a href="{{ $event->boxoffice_url }}">Buy Tickets</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <p class="text-center">
                    <i class="icon ti ti-calendar-cancel icon-lg text-muted m-6"></i>
                </p>
                <p class="text-center">
                    There are no upcoming events.
                </p>
            @endif
        </div>
        <div class="col-xl-3 col-sm-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <span class="avatar avatar-xl rounded" style="background-image: url('{{ Auth::user()->avatarUrl() }}')"></span>
                    </div>
                    <div class="card-title mb-1">{{ Auth::user()->nickname }}</div>
                    <div class="text-secondary">
                        <span class="text-muted">{{ Auth::user()->name }}</span>
                    </div>
                    @if(Auth::user()->clanMemberships)
                        <div class="mt-4">
                            @foreach(Auth::user()->clanMemberships as $clanMember)
                                <a href="{{ route('clans.show', $clanMember->clan->code) }}" class="my-1 mx-2 badge text-decoration-none bg-blue text-blue-fg d-block">{{ $clanMember->clan->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
