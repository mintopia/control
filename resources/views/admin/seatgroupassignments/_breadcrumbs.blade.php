@include('admin.events._breadcrumbs', [
    'active' => false,
])
<li class="breadcrumb-item @if($active ?? false) active @endif"><a
        href="{{ route('admin.events.seatgroups.show', [$event->code, $group->id]) }}">{{ $group->name }}</a></li>
