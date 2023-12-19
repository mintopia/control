@if($tickets)
    <h3 class="mb-2 px-1">{{ $title }}</h3>
    <div class="card mb-2">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped">
                <thead>
                <tr>
                    <th>Nickname</th>
                    <th class="w-1">Seat</th>
                </tr>
                </thead>
                <tbody>
                @foreach($tickets as $ticket)
                    <tr>
                        <td>
                            @if($ticket->user->id === Auth::user()->id)
                                <a href="{{ route('tickets.show', $ticket->id) }}">{{ $ticket->user->nickname }}</a>
                            @else
                                {{ $ticket->user->nickname }}
                            @endif
                        </td>
                        <td>{{ $ticket->seat->label ?? 'None' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
