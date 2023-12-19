@include('admin.seatingplans._breadcrumbs', [
    'active' => false,
])
<li class="breadcrumb-item @if($active ?? false) active @endif"><a
        href="{{ route('admin.events.seatingplans.seats.show', [$event->code, $plan->id, $seat->id]) }}">{{ $seat->label }}</a>
</li>
