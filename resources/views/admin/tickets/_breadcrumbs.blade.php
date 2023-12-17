<li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.tickets.index') }}">Tickets</a></li>
<li class="breadcrumb-item @if($active ?? false) active @endif"><a href="{{ route('admin.tickets.show', $ticket->id) }}">{{ $ticket->reference }}</a></li>

