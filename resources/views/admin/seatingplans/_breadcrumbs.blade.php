@include('admin.events._breadcrumbs', [
    'active' => false,
])
<li class="breadcrumb-item @if($active ?? false) active @endif"><a
        href="{{ route('admin.events.seatingplans.show', [$event->code, $plan->id]) }}">{{ $plan->name }}</a></li>
