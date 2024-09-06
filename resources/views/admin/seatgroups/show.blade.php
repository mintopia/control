@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.seatgroupassignments._breadcrumbs', [
        'active' => true,
    ])
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>{{ $group->name }}</h1>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">ID</div>
                            <div class="datagrid-content">{{ $group->id }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Name</div>
                            <div class="datagrid-content">{{ $group->name }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Seats</div>
                            <div class="datagrid-content">{{ $group->seats->count() }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Event</div>
                            <div class="datagrid-content">
                                <a href="{{ route('admin.events.show', $event->code) }}">{{ $event->name }}</a>
                            </div>
                        </div>
                        @if($group->class)
                            <div class="datagrid-item">
                                <div class="datagrid-title">CSS Class</div>
                                <div class="datagrid-content">{{ $group->class }}</div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer align-content-end d-flex btn-list">
                    <a href="{{ route('admin.events.seatgroups.delete', [$event->code, $group->id]) }}"
                       class="btn btn-outline-danger">
                        <i class="icon ti ti-trash"></i>
                        Delete
                    </a>
                    <a href="{{ route('admin.events.seatgroups.edit', [$event->code, $group->id]) }}"
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
            <h2>Seat Group Assignments</h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="{{ route('admin.events.seatgroups.assignments.create', [$event->code, $group->id]) }}"
                   class="btn btn-primary d-inline-block">
                    <i class="icon ti ti-plus"></i>
                    Add Assignment
                </a>
            </div>
        </div>
    </div>

    <p>
        Assignments allow you to define the constraints for this seat group, multiple of these are treated as an OR condition.
    </p>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Assignment Type</th>
                            <th>Assignment Type ID</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($group->assignments as $assignment)
                            <tr>
                                <td class="text-muted">{{ $assignment->id }}</td>
                                <td>{{ $assignment->assignment_type }}</td>
                                <td>{{ $assignment->assignment_type_id }}</td>

                                <td class="btn-list">
                                    <a class="btn btn-outline-primary ms-auto"
                                       href="{{ route('admin.events.seatgroups.assignments.edit', [$event->code, $group->id, $assignment->id]) }}">
                                        <i class="icon ti ti-edit"></i>
                                        Edit
                                    </a>
                                    <a class="btn btn-outline-danger"
                                       href="{{ route('admin.events.seatgroups.assignments.delete', [$event->code, $group->id, $assignment->id]) }}">
                                        <i class="icon ti ti-trash"></i>
                                        Delete
                                    </a>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="4" class="text-center p-4">
                                    <p>There are no seat group assignments</p>
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
