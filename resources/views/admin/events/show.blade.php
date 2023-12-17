@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.events._breadcrumbs', [
        'active' => true,
    ])
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>{{ $event->name }}</h1>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">ID</div>
                            <div class="datagrid-content">{{ $event->id }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Name</div>
                            <div class="datagrid-content">{{ $event->name }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Code</div>
                            <div class="datagrid-content">{{ $event->code }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Starts</div>
                            <div class="datagrid-content">{{ $event->starts_at->format('d M Y H:i') }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Ends</div>
                            <div class="datagrid-content">{{ $event->ends_at->format('d M Y H:i') }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Box Office URL</div>
                            <div class="datagrid-content">
                                @if($event->boxoffice_url)
                                    <a href="{{ $event->boxoffice_url }}">{{ $event->boxoffice_url }}</a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Seating Locked</div>
                            <div class="datagrid-content">
                                @if($event->seating_locked)
                                    <span class="status status-red">
                                        Yes
                                    </span>
                                @else
                                    <span class="status status-green">
                                        No
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">
                                <span title="{{ $event->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $event->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer align-content-end d-flex btn-list">
                    <a href="{{ route('admin.events.delete', $event->code) }}" class="btn btn-outline-danger">
                        <i class="icon ti ti-trash"></i>
                        Delete
                    </a>

                    <a href="{{ route('admin.events.export', $event->code) }}" class="btn btn-primary-outline ms-auto">
                        <i class="icon ti ti-table-export"></i>
                        Export Ticket List
                    </a>
                    <a href="{{ route('admin.tickets.index', ['event' => $event->code]) }}" class="btn btn-primary-outline">
                        <i class="icon ti ti-ticket"></i>
                        Tickets
                        ({{ $event->tickets()->count() }})
                    </a>
                    <a href="{{ route('admin.tickets.create', ['event' => $event->code]) }}" class="btn btn-primary-outline">
                        <i class="icon ti ti-plus"></i>
                        Add Ticket
                    </a>
                    <a href="{{ route('admin.events.edit', $event->code) }}" class="btn btn-primary">
                        <i class="icon ti ti-edit"></i>
                        Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row align-items-center">
        <div class="col page-header mt-2">
            <h2>Seating Plans</h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="{{ route('admin.events.seats', $event->code) }}" class="btn btn-primary-outline">
                    <i class="icon ti ti-armchair"></i>
                    Manage Seating
                </a>
                <a href="{{ route('admin.events.seatingplans.create', $event->code) }}" class="btn btn-primary d-inline-block">
                    <i class="icon ti ti-plus"></i>
                    Add Seating Plan
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Seats</th>
                            <th>Revision</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($seatingPlans as $plan)
                            <tr>
                                <td class="text-muted">{{ $plan->id }}</td>
                                <td>
                                    <a href="{{ route('admin.events.seatingplans.down', [$event->code, $plan->id]) }}" class="text-decoration-none text-muted">
                                        <i class="icon ti ti-caret-down-filled"></i>
                                    </a>
                                    {{ $plan->order }}
                                    <a href="{{ route('admin.events.seatingplans.up', [$event->code, $plan->id]) }}" class="text-decoration-none text-muted">
                                        <i class="icon ti ti-caret-up-filled"></i>
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('admin.events.seatingplans.show', [$event->code, $plan->id]) }}">{{ $plan->name }}</a>
                                </td>
                                <td>{{ $plan->code }}</td>
                                <td>{{ $plan->seats_count }}</td>
                                <td>{{ $plan->revision }}</td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-4">
                                    <p>There are no seating plans</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row align-items-center">
        <div class="col page-header mt-2">
            <h2>Ticket Types</h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="{{ route('admin.events.tickettypes.create', $event->code) }}" class="btn btn-primary d-inline-block">
                    <i class="icon ti ti-plus"></i>
                    Add Ticket Type
                </a>
            </div>
        </div>
    </div>

    <p>
        This is where you can define the different types of tickets you have, and whether they are able to pick seats or not. This
        also allows you to link them to ticket types from your ticket provider.
    </p>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Has Seats</th>
                            <th>Tickets</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($ticketTypes as $type)
                            <tr>
                                <td class="text-muted">{{ $type->id }}</td>
                                <td>
                                    <a href="{{ route('admin.events.tickettypes.show', [$event->code, $type->id]) }}">{{ $type->name }}</a>
                                </td>
                                <td>
                                    @if($type->has_seat)
                                        <span class="status status-green">Yes</span>
                                    @else
                                        <span class="status status-red">No</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.tickets.index', ['ticket_type_id' => $type->id]) }}">{{ $type->tickets_count }}</a>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="4" class="text-center p-4">
                                    <p>There are no ticket types</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row align-items-center">
        <div class="col page-header mt-2">
            <h2>Ticket Provider Mappings</h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                @if($event->getAvailableEventMappings())
                    <a href="{{ route('admin.events.mappings.create', $event->code) }}" class="btn btn-primary d-inline-block">
                        <i class="icon ti ti-plus"></i>
                        Add Mapping
                    </a>
                @endif
            </div>
        </div>
    </div>

    <p>
        This mapping allows us to link tickets from a ticket provider to this event.
    </p>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Provider</th>
                            <th>External ID</th>
                            <th>External Name</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($event->mappings as $map)
                            <tr>
                                <td class="text-muted">{{ $map->id }}</td>
                                <td>{{ $map->provider->name }}</td>
                                <td>{{ $map->external_id }}</td>
                                <td>
                                    @if($map->name)
                                        {{ $map->name }}
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td class="btn-list">
                                    <a class="btn btn-outline-primary ms-auto" href="{{ route('admin.events.mappings.edit', [$event->code, $map->id]) }}">
                                        <i class="icon ti ti-edit"></i>
                                        Edit
                                    </a>
                                    <a class="btn btn-outline-danger" href="{{ route('admin.events.mappings.delete', [$event->code, $map->id]) }}">
                                        <i class="icon ti ti-trash"></i>
                                        Delete
                                    </a>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="4" class="text-center p-4">
                                    <p>There are no ticket provider mappings</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
