@extends('layouts.app', [
    'activenav' => 'seating',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('seatingplans.index') }}">Seating Plans</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('seatingplans.show', $event->code) }}">{{ $event->name }}</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>{{ $event->name }}</h1>
    </div>
    <div class="row">
        <div class="col-md-3">
            @include('seatingplans._tickets', [
                'title' => 'Your Tickets',
                'tickets' => $tickets[0]
            ])
            @foreach($clans as $clan)
                @include('seatingplans._tickets', [
                    'title' => $clan->name,
                    'tickets' => $tickets[$clan->id] ?? []
                ])
            @endforeach
        </div>
        <div class="col-md-9">
            <div class="card-tabs">
                <ul class="nav nav-tabs" role="tablist">
                    @foreach($event->seatingPlans as $i => $plan)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link @if($i === 0) active @endif" href="#tab-plan-{{ $plan->code }}" data-bs-toggle="tab" @if($i === 0) aria-selected="true" @endif role="tab">{{ $plan->name }}</a>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content">
                    @foreach($event->seatingPlans as $i => $plan)
                        <div id="tab-plan-{{ $plan->code }}" class="card tab-pane @if($i === 0) active show @endif" role="tabpanel" style="min-width: {{ collect($seats[$plan->id] ?? [])->max('x') * 2 + 4 }}em;">
                            <div class="card-body p-0" style="min-height: {{ (collect($seats[$plan->id] ?? [])->max('y') * 2) + 4 }}em;">
                                <div class="seating-plan">
                                    @foreach($seats[$plan->id] ?? [] as $seat)
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
                                            if (in_array($seat->id, $clanSeats)) {
                                                $class = 'seat-clan';
                                            }
                                            if (in_array($seat->id, $mySeats)) {
                                                $class = 'seat-mine';
                                            }

                                        @endphp
                                        <{{ $seat->canPick ? 'a' : 'div' }} class="d-block seat {{ $seat->class }} {{ $class }}"
                                             @if($seat->canPick) href="{{ route('seats.edit', $seat->id) }}" @endif
                                             style="left: {{ $seat->x * 2 }}em; top: {{ $seat->y * 2 }}em;"
                                             data-bs-trigger="hover" data-bs-toggle="popover"
                                             data-bs-placement="right"
                                             title="{{ $seat->description }} {{ $seat->label }}"
                                             data-bs-content="{{ $name }}"
                                        ></{{ $seat->canPick ? 'a' : 'div' }}>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
