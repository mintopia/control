@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.tickets.index') }}">Tickets</a></li>
@endsection

@section('content')
    <div class="row g-2 align-items-center mb-4">
        <div class="col page-header mt-2">
            <h1>Tickets</h1>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="{{ route('admin.tickets.import') }}" class="btn btn-primary d-inline-block">
                    <i class="icon ti ti-table-import"></i>
                    Import
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 col-lg-3 mb-4">
            <form action="{{ route('admin.tickets.index') }}" method="get" class="card">
                <div class="card-header">
                    <h3 class="card-title">Search</h3>
                </div>
                <div class="card-body">
                    @include('partials._searchtextfield', [
                        'name' => 'ID',
                        'property' => 'id',
                    ])
                    @include('partials._searchtextfield', [
                        'name' => 'User ID',
                        'property' => 'user_id',
                    ])
                    @include('partials._searchtextfield', [
                        'name' => 'Reference',
                        'property' => 'reference',
                    ])

                    @include('partials._searchselectfield', [
                        'name' => 'Ticket Provider',
                        'property' => 'ticket_provider_id',
                        'options' => $providers,
                        'valueProperty' => 'id',
                        'nameProperty' => 'name',
                    ])

                    @include('partials._searchtextfield', [
                        'name' => 'External ID',
                        'property' => 'external_id',
                    ])

                    @include('partials._searchselectfield', [
                        'name' => 'Event',
                        'property' => 'event',
                        'options' => $events,
                        'valueProperty' => 'code',
                        'nameProperty' => 'name',
                    ])

                    @include('partials._searchtextfield', [
                        'name' => 'Ticket Type ID',
                        'property' => 'ticket_type_id',
                    ])

                    @include('partials._searchtextfield', [
                        'name' => 'Seat',
                        'property' => 'seat',
                    ])
                    @include('partials._searchtextfield', [
                        'name' => 'Original Email',
                        'property' => 'original_email',
                    ])
                </div>
                <div class="card-footer d-flex">
                    <button class="btn btn-primary ms-auto" type="submit">Search</button>
                </div>
            </form>
        </div>

        <div class="col-lg-9 col-md-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover table-outline table-vcenter text-nowrap card-table">
                        <thead>
                        <tr>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'ID',
                                    'field' => 'id',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'External ID',
                                    'field' => 'external_id',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Reference',
                                    'field' => 'reference',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Event',
                                    'field' => 'event',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Type',
                                    'field' => 'type',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'User',
                                    'field' => 'user',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Seat',
                                    'field' => 'seat',
                                ])
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($tickets as $ticket)
                            <tr>
                                <td class="text-muted">{{ $ticket->id }}</td>
                                <td>
                                    <span class="user-select-all">{{ $ticket->external_id }}</span><br/>
                                    <span class="text-muted">{{ $ticket->provider->name }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.tickets.show', $ticket->id) }}">{{ $ticket->reference }}</a>
                                </td>
                                <td>
                                    <a href="{{ route('admin.events.show', $ticket->event->code) }}">{{ $ticket->event->name }}</a>
                                </td>
                                <td>{{ $ticket->type->name }}</td>
                                <td>
                                    @if($ticket->user_id)
                                        <a href="{{ route('admin.users.show', $ticket->user->id) }}">{{ $ticket->user->nickname }}</a>
                                    @else
                                        <span class="text-muted">{{ $ticket->original_email }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ticket->seat)
                                        <a href="{{ route('admin.events.seats', ['event' => $ticket->event->code, 'ticket_id' => $ticket->id]) }}">{{ $ticket->seat->label }}</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @include('partials._pagination', [
                    'page' => $tickets
                ])
            </div>
        </div>
    </div>
@endsection
