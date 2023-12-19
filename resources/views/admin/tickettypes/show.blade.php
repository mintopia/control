@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.tickettypes._breadcrumbs', [
        'active' => true,
    ])
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>{{ $type->name }}</h1>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">ID</div>
                            <div class="datagrid-content">{{ $type->id }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Name</div>
                            <div class="datagrid-content">{{ $type->name }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Has Seats</div>
                            <div class="datagrid-content">
                                @if($type->has_seat)
                                    <span class="status status-green">
                                        Yes
                                    </span>
                                @else
                                    <span class="status status-red">
                                        No
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Event</div>
                            <div class="datagrid-content">
                                <a href="{{ route('admin.events.show', $event->code) }}">{{ $event->name }}</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer align-content-end d-flex btn-list">
                    <a href="{{ route('admin.events.tickettypes.delete', [$event->code, $type->id]) }}"
                       class="btn btn-outline-danger">
                        <i class="icon ti ti-trash"></i>
                        Delete
                    </a>
                    <a href="{{ route('admin.tickets.index', ['ticket_type_id' => $type->id]) }}"
                       class="btn btn-primary-outline ms-auto">
                        <i class="icon ti ti-ticket"></i>
                        Tickets
                        ({{ $type->tickets()->count() }})
                    </a>
                    <a href="{{ route('admin.events.tickettypes.edit', [$event->code, $type->id]) }}"
                       class="btn btn-primary">
                        <i class="icon ti ti-edit"></i>
                        Edit
                    </a>
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
                @if(count($event->getAvailableTicketMappings()) > 0)
                    <a href="{{ route('admin.events.tickettypes.mappings.create', [$event->code, $type->id]) }}"
                       class="btn btn-primary d-inline-block">
                        <i class="icon ti ti-plus"></i>
                        Add Mapping
                    </a>
                @endif
            </div>
        </div>
    </div>

    <p>
        This mapping allows us to link tickets from a ticket provider to this ticket type.
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
                        @forelse($type->mappings as $map)
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
                                    <a class="btn btn-outline-primary ms-auto"
                                       href="{{ route('admin.events.tickettypes.mappings.edit', [$event->code, $type->id, $map->id]) }}">
                                        <i class="icon ti ti-edit"></i>
                                        Edit
                                    </a>
                                    <a class="btn btn-outline-danger"
                                       href="{{ route('admin.events.tickettypes.mappings.delete', [$event->code, $type->id, $map->id]) }}">
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
