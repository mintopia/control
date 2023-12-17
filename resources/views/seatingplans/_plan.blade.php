<div class="card-body p-0">
    <div class="seating-plan" style="
        @if($plan->image_url)
            background-image:url('{{ $plan->image_url }}');
        @endif
        min-height: {{ (collect($seats[$plan->id] ?? [])->max('y') * 2) + 4 }}em;"
        >
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
                if (count($allTickets) === 0) {
                    $canPick = false;
                }

                if (in_array($seat->id, $clanSeats)) {
                    $class = 'seat-clan';
                }
                if (in_array($seat->id, $mySeats)) {
                    $class = 'seat-mine';
                }

                $tooltipContents = "<span class=\"fs-4\">{$name}</span>";
                if ($seat->clans) {
                    $tooltipContents .= '<br /><span class="badges-list mt-2 d-block">';
                    foreach ($seat->clans as $clan) {
                        $tooltipContents .= "<span class=\"badge bg-primary text-primary-fg\">{$clan}</span>";
                    }
                    $tooltipContents .= '</span>';
                }

            @endphp
            <{{ $canPick ? 'a' : 'div' }} class="d-block seat {{ $seat->class }} {{ $class }}"
                 @if($canPick) href="{{ route('seats.edit', $seat->id) }}" @endif
                 style="left: {{ $seat->x * 2 }}em; top: {{ $seat->y * 2 }}em;"
                 data-bs-trigger="hover" data-bs-toggle="popover"
                 data-bs-placement="right" data-bs-html="true"
                 title="{{ $seat->description }} {{ $seat->label }}"
                 data-bs-content="{{ $tooltipContents }}"
            ></{{ $canPick ? 'a' : 'div' }}>
        @endforeach
    </div>
</div>
