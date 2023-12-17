@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.tickets._breadcrumbs', [
        'active' => true,
    ])
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>{{ $ticket->reference }}</h1>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">ID</div>
                            <div class="datagrid-content">{{ $ticket->id }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Reference</div>
                            <div class="datagrid-content">{{ $ticket->reference }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">User</div>
                            <div class="datagrid-content">
                                <a href="{{ route('admin.users.show', $ticket->user->id) }}">{{ $ticket->user->nickname }}</a>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Event</div>
                            <div class="datagrid-content">
                                <a href="{{ route('admin.events.show', $ticket->event->code) }}">{{ $ticket->event->name }}</a>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Type</div>
                            <div class="datagrid-content">
                                <a href="{{ route('admin.events.tickettypes.show', [$ticket->event->code, $ticket->type->id]) }}">{{ $ticket->type->name }}</a>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Seat</div>
                            <div class="datagrid-content">
                                @if ($ticket->seat)
                                    <a href="#">{{ $ticket->seat->label }}</a>
                                @elseif($ticket->canPickSeat())
                                    <a href="#">None</a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Transfer Code</div>
                            <div class="datagrid-content">
                                @if($ticket->transfer_code)
                                    {{ $ticket->transfer_code }}
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Provider</div>
                            <div class="datagrid-content">{{ $ticket->provider->name }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">{{ $ticket->provider->name }} ID</div>
                            <div class="datagrid-content">{{ $ticket->external_id }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">
                                <span title="{{ $ticket->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $ticket->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer align-content-end d-flex btn-list">
                    <a href="{{ route('admin.tickets.delete', $ticket->id) }}" class="btn btn-outline-danger">
                        <i class="icon ti ti-trash"></i>
                        Delete
                    </a>
                    <a href="{{ route('admin.tickets.edit', $ticket->id) }}" class="btn btn-primary ms-auto">
                        <i class="icon ti ti-edit"></i>
                        Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection
