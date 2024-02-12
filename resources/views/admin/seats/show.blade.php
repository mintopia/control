@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.seats._breadcrumbs', [
        'active' => true,
    ])
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>{{ $seat->label }}</h1>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">ID</div>
                            <div class="datagrid-content">{{ $seat->id }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Position</div>
                            <div class="datagrid-content">{{ $seat->x }}, {{ $seat->y }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Row</div>
                            <div class="datagrid-content">{{ $seat->row }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Number</div>
                            <div class="datagrid-content">{{ $seat->number }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Label</div>
                            <div class="datagrid-content">{{ $seat->label }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Description</div>
                            <div class="datagrid-content">{{ $seat->description }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Disabled</div>
                            <div class="datagrid-content">
                                @if($seat->disabled)
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
                            <div class="datagrid-title">Seating Plan</div>
                            <div class="datagrid-content">
                                <a href="{{ route('admin.events.seatingplans.show', [$event->code, $plan->id]) }}">{{ $plan->name }}</a>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Ticket</div>
                            <div class="datagrid-content">
                                @if ($seat->ticket)
                                    <a href="{{ route('admin.tickets.show', [$seat->ticket->id]) }}">{{ $seat->ticket->user->nickname ?? $seat->ticket->original_email }}</a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Assigned Clan</div>
                            <div class="datagrid-content">
                                @if($seat->clan)
                                    <a href="{{ route('admin.clans.show', [$seat->clan->code]) }}">{{ $seat->clan->name}}</a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer align-content-end d-flex btn-list">
                    <a href="{{ route('admin.events.seatingplans.seats.delete', [$event->code, $plan->id, $seat->id]) }}"
                       class="btn btn-outline-danger">
                        <i class="icon ti ti-trash"></i>
                        Delete
                    </a>

                    <a href="{{ route('admin.events.seatingplans.seats.unseat', [$event->code, $plan->id, $seat->id]) }}"
                       class="btn btn-primary-outline ms-auto @if(!$seat->ticket) disabled @endif">
                        <i class="icon ti ti-door-exit"></i>
                        Unseat Ticket
                    </a>
                    <a href="{{ route('admin.events.seatingplans.seats.edit', [$event->code, $plan->id, $seat->id]) }}"
                       class="btn btn-primary">
                        <i class="icon ti ti-edit"></i>
                        Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection
