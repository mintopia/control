@extends('layouts.app', [
    'activenav' => 'seating',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('seatingplans.index') }}">Seating Plans</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('seatingplans.show', $event->code) }}">{{ $event->name }}</a>
    </li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>
            {{ $event->name }}

            @if($event->draft)
                <span class="badge bg-muted text-muted-fg">Draft</span>
            @endif
        </h1>
    </div>

    @if(!$event->seating_locked)
        @if($currentTicket)
            <p>
                Choose a seat for <strong>{{ $currentTicket->user->nickname }}</strong>.
            </p>
        @elseif(count($allTickets) > 0)
            <p>
                Select a ticket to assign it a seat.
            </p>
        @endif
    @endif

    <div class="row">
        <div class="col-md-3">
            @if($currentTicket)
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">{{ $currentTicket->user->nickname }}</h3>
                        @if($currentTicket->user->clanMemberships)
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
                            @if($currentTicket->user->id === Auth::user()->id)
                            <div class="datagrid-item">
                                <div class="datagrid-title">Ticket</div>
                                <div class="datagrid-content">
                                    <a href="{{ route('tickets.show', $currentTicket->id) }}">{{ $currentTicket->reference }}</a>
                                </div>
                            </div>
                            @endif
                            <div class="datagrid-item">
                                <div class="datagrid-title">Type</div>
                                <div class="datagrid-content">{{ $currentTicket->type->name }}</div>
                            </div>
                        </div>
                    </div>
                    @if ($currentTicket->seat)
                        <div class="card-footer align-content-end d-flex btn-list">
                            <a href="{{ route('seatingplans.show', $event->code) }}" class="btn btn-link">Cancel</a>
                            @if ($currentTicket->canPickSeat())
                                <a href="{{ route('seatingplans.unseat', [$event->code, $currentTicket->id]) }}"
                                   class="btn btn-primary-outline ms-auto">
                                    <i class="icon ti ti-door-exit"></i>
                                    Unseat
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
            @if(count($responsibleTickets) > 0)
                <div class="card mb-2">
                    <div class="card-body">
                        <h3 class="card-title">
                            @if($event->seating_locked)
                                Tickets
                            @else
                                Select Ticket
                            @endif
                        </h3>
                        <ul class="list-unstyled">
                            @foreach($responsibleTickets as $ticket)
                                <li class="my-1 d-flex">
                                    @if($event->seating_locked)
                                        {{ $ticket->user->nickname }}
                                    @else
                                        <a href="{{ route('seatingplans.choose', [$event->code, $ticket->id]) }}">{{ $ticket->user->nickname }}</a>
                                    @endif
                                    @if ($ticket->user->id === Auth::user()->id)
                                        <span class="mx-1 text-muted" title="Ticket Reference"> - {{ $ticket->reference }}</span>
                                    @endif
                                    @if ($ticket->seat)
                                        <span class="badge ms-auto bg-muted text-muted-fg d-inline-block">{{ $ticket->seat->label }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @if(count($responsibleTickets) === 0 && !$currentTicket)
                <p class="text-center mt-4">
                    <i class="icon ti ti-ticket-off icon-lg text-muted m-6 mt-4"></i>
                </p>
                <p class="text-muted">You have no tickets available to seat</p>
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
                            @include('seatingplans._plan')
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
@push('footer')
    <script type="text/javascript">
        const INTERVAL = 10;

        let plans = {
            @foreach($event->seatingPlans as $plan)
            '{{ $plan->code }}': {{ $plan->revision }},
            @endforeach
        }

        function updatePlan(code, version) {
            axios.get('{{ route('seatingplans.show', $event->code) }}', {
                params: {
                    plan: code,
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                responseType: 'text',
            })
                .then(response => {
                    if (response.status !== 200) {
                        throw new Error('Unable to update seating plan');
                    }
                    return response.data;
                })
                .then(data => {
                    let container = document.getElementById('tab-plan-' + code);
                    container.innerHTML = data;
                    plans[code] = version;
                    container.querySelectorAll('[data-bs-toggle="popover"]').forEach((element) => {
                        new bootstrap.Popover(element);
                    });
                });
        }

        function checkRevisions() {
            axios.get('{{ route('api.v1.events.seatingplans.index', ['event' => $event->code]) }}', {
                headers: {
                    'Accept': 'application/json',
                },
            }).then(response => {
                if (response.status !== 200) {
                    throw new Error('Unable to fetch revision');
                }
                return response.data;
            }).then(data => {
                data.data.forEach((plan) => {
                    if (plans[plan.code] && plans[plan.code] !== plan.revision) {
                        updatePlan(plan.code, plan.revision);
                    }
                });
            }).finally(() => {
                setTimeout(checkRevisions, INTERVAL * 1000);
            })
        }

        function selectTab() {
            if (window.location.hash === '') {
                return;
            }
            const element = document.querySelector("[href='" + window.location.hash + "']");
            if (element) {
                bootstrap.Tab.getInstance(element).show()
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(checkRevisions, INTERVAL * 1000)
            selectTab();
        });
    </script>
@endpush
