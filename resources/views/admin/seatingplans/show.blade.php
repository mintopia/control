@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.seatingplans._breadcrumbs', [
        'active' => true,
    ])
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>{{ $plan->name }}</h1>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">ID</div>
                            <div class="datagrid-content">{{ $plan->id }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Name</div>
                            <div class="datagrid-content">{{ $plan->name }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Code</div>
                            <div class="datagrid-content">{{ $plan->code }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Background Image URL</div>
                            <div class="datagrid-content">
                                @if($plan->image_url)
                                    <a href="{{ $plan->image_url }}">{{ $plan->image_url }}</a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Event</div>
                            <div class="datagrid-content">
                                <a href="{{ route('admin.events.show', $event->code) }}">{{ $event->name }}</a>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Revision</div>
                            <div class="datagrid-content">{{ $plan->revision }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">
                                <span title="{{ $plan->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $plan->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer align-content-end d-flex btn-list">
                    <a href="{{ route('admin.events.seatingplans.delete', [$event->code, $plan->id]) }}" class="btn btn-outline-danger">
                        <i class="icon ti ti-trash"></i>
                        Delete
                    </a>

                    <a href="{{ route('admin.events.seatingplans.refresh', [$event->code, $plan->id]) }}" class="btn btn-primary-outline ms-auto">
                        <i class="icon ti ti-refresh"></i>
                        Refresh
                    </a>
                    <a href="{{ route('admin.events.seatingplans.edit', [$event->code, $plan->id]) }}" class="btn btn-primary">
                        <i class="icon ti ti-edit"></i>
                        Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row align-items-center">
        <div class="col page-header mt-2">
            <h2>Seats</h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="{{ route('admin.events.seatingplans.import', [$event->code, $plan->id]) }}" class="btn btn-outline-primary">
                    <i class="icon ti ti-table-import"></i>
                    Import
                </a>
                <a href="{{ route('admin.events.seatingplans.export', [$event->code, $plan->id]) }}" class="btn btn-outline-primary">
                    <i class="icon ti ti-table-export"></i>
                    Export
                </a>
                <a href="{{ route('admin.events.seatingplans.seats.create', [$event->code, $plan->id]) }}" class="btn btn-primary">
                    <i class="icon ti ti-plus"></i>
                    Add Seat
                </a>
            </div>
        </div>
    </div>

    <p class="mt-2 mb-3">
        If you want to make changes to multiple seats, it may be quicker and easier to export the seats, edit them in
        Excel or Google Sheets, and then import the changes.
    </p>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="seating-plan" style="
                        @if($plan->image_url)
                            background-image:url('{{ $plan->image_url }}');
                        @endif
                        min-height: {{ ($seats->max('y') * 2) + 4 }}em;"
                        >
                        @foreach($seats as $seat)
                            @php
                                $class = 'available';
                                $name = 'Available';
                                if ($seat->disabled) {
                                    $class = 'disabled';
                                    $name = 'Not Available';
                                }
                                if ($seat->nickname) {
                                    $name = $seat->nickname;
                                }
                                if ($seat->ticket) {
                                    $class = 'taken';
                                }
                            @endphp
                            <a class="d-block seat {{ $seat->class }} {{ $class }}"
                                href="{{ route('admin.events.seatingplans.seats.show', [$event->code, $plan->id, $seat->id]) }}"
                                style="left: {{ $seat->x * 2 }}em; top: {{ $seat->y * 2 }}em;"
                                data-bs-trigger="hover" data-bs-toggle="popover"
                                data-bs-placement="right"
                                title="{{ $seat->description }} {{ $seat->label }}"
                                data-bs-content="{{ $name }}"
                            ></a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
