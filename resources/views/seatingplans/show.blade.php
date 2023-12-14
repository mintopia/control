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
        let plans = {
            @foreach($event->seatingPlans as $plan)
                '{{ $plan->code }}': {{ $plan->revision }},
            @endforeach
        }

        function updatePlan(code, version) {
            fetch('{{ route('seatingplans.show', $event->code) }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Unable to update seating plan');
                }
                return response.text();
            })
            .then(data => {
                document.getElementById('tab-plan-' + code).innerHTML = data;
                plans[code] = version;
            });
        }

        function checkRevisions() {
            fetch('{{ route('api.v1.events.seatingplans.index', ['event' => $event->code]) }}', {
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'include',
            }).then(response => {
                if (!response.ok) {
                    throw new Error('Unable to fetch revision');
                }
                return response.json();
            }).then(data => {
                console.log(data);
                data.data.forEach((plan) => {
                    if (plans[plan.code] && plans[plan.code] !== plan.revision) {
                        updatePlan(plan.code, plan.version);
                    }
                });
            }).finally(() => {
                setTimeout(checkRevisions, 30000);
            })
        }

        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(checkRevisions, 30000)
        });
    </script>
@endpush
