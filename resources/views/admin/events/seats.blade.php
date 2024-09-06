@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.events._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.events.seats', $event->code) }}">Manage Seating</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Manage Seating</h1>
    </div>

    @if ($currentTicket)
        <p>
            @can('admin')
                Choose a seat for <strong>{{ $currentTicket->user->nickname ?? $currentTicket->original_email }}</strong>.
            @else
                Choose a seat for <strong>{{ $currentTicket->user->nickname ?? $currentTicket->external_id }}</strong>.
            @endcan
        </p>
    @else
        <p>
            Select a ticket to assign it a seat, or select a seat to move the user.
        </p>
    @endif

    <div class="row">
        <div class="col-md-3">
            @if($currentTicket)
                <div class="card mb-4">
                    <div class="card-body">
                        @can('admin')
                            <h3 class="card-title">{{ $currentTicket->user->nickname ?? $currentTicket->original_email }}</h3>
                        @else
                            <h3 class="card-title">{{ $currentTicket->user->nickname ?? $currentTicket->external_id }}</h3>
                        @endcan
                        @if($currentTicket->user && $currentTicket->user->clanMemberships)
                            <p class="card-subtitle">

                                <span class="badge-list">
                                    @foreach($currentTicket->user->clanMemberships()->with('clan')->get() as $clanMember)
                                        <span class="badge bg-muted text-muted-fg">{{ $clanMember->clan->name }}</span>
                                    @endforeach
                                </span>
                            </p>
                        @endif
                        <div class="datagrid">
                            <div class="datagrid-item">
                                <div class="datagrid-title">Current Seat</div>
                                <div class="datagrid-content">
                                    @if ($currentTicket->seat)
                                        {{ $currentTicket->seat->label }}
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Ticket ID</div>
                                <div class="datagrid-content">
                                    <a href="{{ route('admin.tickets.show', $currentTicket->id) }}">{{ $currentTicket->id }}</a>
                                </div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Type</div>
                                <div class="datagrid-content">{{ $currentTicket->type->name }}</div>
                            </div>
                        </div>
                    </div>
                    @if ($currentTicket->seat)
                        <div class="card-footer align-content-end d-flex btn-list">
                            <a href="{{ route('admin.events.seats', $event->code) }}" class="btn btn-link">Cancel</a>
                            @if ($currentTicket->canPickSeat())
                                <a href="{{ route('admin.events.seats.unseat', [$event->code, $currentTicket->id]) }}"
                                   class="btn btn-primary-outline ms-auto">
                                    <i class="icon ti ti-door-exit"></i>
                                    Unseat
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
            @if(count($tickets) > 0)
                <div class="card mb-2">
                    <div class="card-body">
                        <h3 class="card-title">Unseated Tickets</h3>
                        <p class="card-subtitle">
                            Select a ticket to choose a seat.
                        </p>
                        <ul class="list-unstyled">
                            @foreach($tickets as $ticket)
                                <li class="my-1">
                                    <a href="{{ route('admin.events.seats', ['event' => $event, 'ticket_id' => $ticket->id]) }}">
                                        @can('admin')
                                            {{ $ticket->user->nickname ?? $ticket->original_email }}
                                        @else
                                            {{ $ticket->user->nickname ?? $ticket->external_id }}
                                        @endcan
                                    </a>
                                    <span class="badge-list">
                                    @if ($ticket->user)
                                        @foreach($ticket->user->clanMemberships as $clanMember)
                                            <span
                                                class="badge bg-muted text-muted-fg">{{ $clanMember->clan->name }}</span>
                                        @endforeach
                                    @endif
                                </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @if(count($tickets) === 0 && !$currentTicket)
                <p class="text-center mt-4">
                    <i class="icon ti ti-ticket-off icon-lg text-muted m-6 mt-4"></i>
                </p>
            @endif
        </div>
        <div class="col-md-9">
            <div class="card-tabs">
                <ul class="nav nav-tabs" role="tablist">
                    @foreach($event->seatingPlans as $i => $plan)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link @if($i === 0) active @endif" href="#tab-plan-{{ $plan->code }}"
                               data-bs-toggle="tab" @if($i === 0) aria-selected="true"
                               @endif role="tab">{{ $plan->name }}</a>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content">
                    @foreach($event->seatingPlans as $i => $plan)
                        <div id="tab-plan-{{ $plan->code }}" class="card tab-pane @if($i === 0) active show @endif"
                             role="tabpanel"
                             style="min-width: {{ collect($seats[$plan->id] ?? [])->max('x') * 2 + 4 }}em;">
                            <div class="card-body p-0">
                                <div class="seating-plan" style="
                                   @if($plan->image_url)
                                       background-image:url('{{ $plan->image_url }}');
                                       min-height: {{ $plan->image_height}}px;
                                       min-width: {{ $plan->image_width}}px";
                                   @else
                                       min-height: {{ (collect($seats[$plan->id] ?? [])->max('y') * 2) + 4 }}em;"
                                   @endif
                                >
                                    @foreach($seats[$plan->id] as $seat)
                                        @php
                                            $link = null;
                                            if ($currentTicket) {
                                                $link = route('admin.events.seats.pick', [$event->code, $currentTicket->id, $seat->id]);
                                            } elseif ($seat->ticketId) {
                                                $link = route('admin.events.seats', ['event' => $event->code, 'ticket_id' => $seat->ticketId]);
                                            }
                                            $class = 'available';
                                            $name = 'Available';
                                            if ($seat->disabled) {
                                                $class = 'disabled';
                                                $name = 'Not Available';
                                            }
                                            if ($seat->ticket) {
                                                $class = 'taken';
                                                if(Request::user()->hasRole('admin'))
                                                    $name = $seat->original_email;
                                                else
                                                    $name = $seat->external_id;
                                            }
                                            if ($seat->nickname) {
                                                $name = $seat->nickname;
                                            }
                                            if ($currentTicket && $currentTicket->id == $seat->ticketId) {
                                                $class = 'seat-mine';
                                            }

                                            $tooltipContents = "<span class=\"fs-4\">{$name}</span>";
                                            if ($seat->clans) {
                                                $tooltipContents .= '<br /><span class="badges-list mt-2">';
                                                foreach ($seat->clans as $clan) {
                                                    $tooltipContents .= "<span class=\"badge bg-primary text-primary-fg\">{$clan}</span>";
                                                }
                                                $tooltipContents .= '</span>';
                                            }
                                        @endphp
                                    <a class="d-block seat {{ $seat->class }} {{ $class }}"
                                           @if($link)
                                               href="{{ $link }}"
                                           @endif
                                           style="left: {{ $seat->x * 0.02 * $plan->scale }}em; top: {{ $seat->y * 0.02 * $plan->scale }}em; width: {{ 0.019 * $plan->scale }}em; height: {{ 0.019 * $plan->scale }}em;"
                                           data-bs-trigger="hover" data-bs-toggle="popover"
                                           data-bs-placement="right" data-bs-html="true"
                                           title="{{ $seat->description }} {{ $seat->label }}"
                                           data-bs-content="{{ $tooltipContents }}"
                                        ></a>
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
