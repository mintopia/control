@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.events.index') }}">Events</a></li>
@endsection

@section('content')
    <div class="row g-2 align-items-center mb-4">
        <div class="col page-header mt-2">
            <h1>Events</h1>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="{{ route('admin.events.create') }}" class="btn btn-primary d-inline-block">
                    <i class="icon ti ti-plus"></i>
                    Create Event
                </a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 col-lg-3 mb-4">
            <form action="{{ route('admin.events.index') }}" method="get" class="card">
                <div class="card-header">
                    <h3 class="card-title">Search</h3>
                </div>
                <div class="card-body">
                    @include('partials._searchtextfield', [
                        'name' => 'ID',
                        'property' => 'id',
                    ])
                    @include('partials._searchtextfield', [
                        'name' => 'Name',
                        'property' => 'name',
                    ])
                    @include('partials._searchtextfield', [
                        'name' => 'Code',
                        'property' => 'code',
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
                                    'title' => 'Name',
                                    'field' => 'name',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Code',
                                    'field' => 'code',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Starts',
                                    'field' => 'starts_at',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Tickets',
                                    'field' => 'tickets_count',
                                ])
                            </th>
                            <th>
                                @include('partials._sortheader', [
                                    'title' => 'Created',
                                    'field' => 'created_at',
                                ])
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($events as $event)
                            <tr>
                                <td class="text-muted">{{ $event->id }}</td>
                                <td>
                                    <a href="{{ route('admin.events.show', $event->code) }}">{{ $event->name }}</a>
                                </td>
                                <td>{{ $event->code }}</td>
                                <td>
                                    {{ $event->starts_at->format('d M Y H:i') }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.tickets.index', ['event_id' => $event->id]) }}">{{ $event->tickets_count }}</a>
                                </td>
                                <td>
                                        <span title="{{ $event->created_at->format('Y-m-d H:i:s') }}">
                                            {{ $event->created_at->diffForHumans() }}
                                        </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @include('partials._pagination', [
                    'page' => $events
                ])
            </div>
        </div>
    </div>
@endsection
