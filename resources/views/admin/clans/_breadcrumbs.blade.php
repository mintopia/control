<li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clans.index') }}">Clans</a></li>
<li class="breadcrumb-item @if($active ?? false) active @endif"><a href="{{ route('admin.clans.show', $clan->code) }}">{{ $clan->name }}</a></li>

