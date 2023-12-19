@php use Carbon\Carbon; @endphp
@extends('layouts.app', [
    'activenav' => 'seating',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('seatingplans.index') }}">Seating Plans</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Seating Plans</h1>
    </div>

    <div class="row mb-2">
        <div class="col mb-4">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>Event</th>
                            <th>Starts</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($events as $event)
                            <tr>
                                <td>
                                    <a href="{{ route('seatingplans.show', $event->code) }}">
                                        {{ $event->name }}
                                    </a>
                                </td>
                                <td>{{ $event->starts_at->format('jS F Y') }}
                                    at {{ $event->starts_at->format('gA') }}</td>
                                <td class="text-end">
                                    @if($event->ends_at > Carbon::now() && $event->boxoffice_url)
                                        <a href="{{ $event->boxoffice_url }}">Get Tickets</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center p-4">
                                    <p>There are no events.</p>
                                </td>
                            </tr>
                        @endforelse
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
