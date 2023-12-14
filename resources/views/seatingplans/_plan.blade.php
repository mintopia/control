<div class="card-body p-0" style="min-height: {{ (collect($seats[$plan->id] ?? [])->max('y') * 2) + 4 }}em;">
    <div class="seating-plan">
        @foreach($seats[$plan->id] ?? [] as $seat)
            @php
                $class = 'available';
                $name = 'Available';
                $canPick = $seat->canPick;
                if ($seat->disabled) {
                    $class = 'disabled';
                    $name = 'Not Available';
                }
                if ($seat->nickname) {
                    $name = $seat->nickname;
                }
                if ($seat->ticket) {
                    $class = 'taken';
                    if (!in_array($seat->id, $responsibleSeats)) {
                        $canPick = false;
                    }
                }
                if (in_array($seat->id, $clanSeats)) {
                    $class = 'seat-clan';
                }
                if (in_array($seat->id, $mySeats)) {
                    $class = 'seat-mine';
                }

            @endphp
            <{{ $canPick ? 'a' : 'div' }} class="d-block seat {{ $seat->class }} {{ $class }}"
                 @if($canPick) href="{{ route('seats.edit', $seat->id) }}" @endif
                 style="left: {{ $seat->x * 2 }}em; top: {{ $seat->y * 2 }}em;"
                 data-bs-trigger="hover" data-bs-toggle="popover"
                 data-bs-placement="right"
                 title="{{ $seat->description }} {{ $seat->label }}"
                 data-bs-content="{{ $name }}"
            ></{{ $canPick ? 'a' : 'div' }}>
        @endforeach
    </div>
</div>
