<li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Events</a></li>
<li class="breadcrumb-item @if($active ?? false) active @endif"><a
        href="{{ route('admin.events.show', $event->code) }}">{{ $event->name }}</a></li>

