<div class="card-body p-0">
    <div class="seating-plan" style="
        @if($plan->image_url)
            background-image:url('{{ $plan->image_url }}');
            min-height: {{ $plan->image_height}}px;
            min-width: {{ $plan->image_width}}px";
        @else
            min-height: {{ (collect($seats[$plan->id] ?? [])->max('y') * 2) + 4 }}em;"
        @endif
    >
        @foreach($seats[$plan->id] ?? [] as $seat)
            @php
                $url = null;
                if ($currentTicket) {
                    $url = route('seatingplans.select', [$event->code, $currentTicket->id, $seat->id]);
                }
                $class = 'available';
                $name = 'Available';
                $canPick = $seat->canPick;
                if ($seat->disabled) {
                    $class = 'disabled';
                    $name = 'Not Available';
                }
                if ($seat->ticket) {
                    $class = 'taken';
                    $canPick = false;
                    $name = 'Occupied';
                }
                if($seat->group) {
                    if(!in_array($seat->group, $seatGroups)) {
                        $canPick = false;
                        $name = 'Not Available';
                        if ($class === 'available') {
                            $class = '';
                        }
                    }
                }
                if ($seat->nickname) {
                    $name = $seat->nickname;
                }
                if (!$currentTicket) {
                    if (in_array($seat->id, $responsibleSeats)) {
                        $canPick = true;
                        $url = route('seatingplans.choose', [$event->code, $seat->ticketId]);
                    } else {
                        $canPick = false;
                    }
                }

                if (in_array($seat->id, $clanSeats) || in_array($seat->id, $mySeats)) {
                    $class = 'seat-clan';
                }
                if ($currentTicket && $seat->ticketId === $currentTicket->id) {
                    $class = 'seat-mine';
                }

                $tooltipContents = "<span class=\"fs-4\">{$name}</span>";
                if ($seat->clans) {
                    $tooltipContents .= '<br /><span class="badges-list mt-2">';
                    foreach ($seat->clans as $clan) {
                        $tooltipContents .= "<span class=\"badge bg-primary text-primary-fg\">{$clan}</span>";
                    }
                    $tooltipContents .= '</span>';
                }

            @endphp
        <{{ $canPick ? 'a' : 'div' }} class="d-block seat {{ $seat->class }} {{ $class }}"
            @if($canPick)
                href="{{ $url }}"
            @endif
            style="left: {{ $seat->x * 0.02 * $plan->scale }}em; top: {{ $seat->y * 0.02 * $plan->scale }}em; width: {{ 0.019 * $plan->scale }}em; height: {{ 0.019 * $plan->scale }}em;"
            data-bs-trigger="hover" data-bs-toggle="popover"
            data-bs-placement="right" data-bs-html="true"
            title="{{ $seat->description }} {{ $seat->label }}"
            data-bs-content="{{ $tooltipContents }}"
            >
    </{{ $canPick ? 'a' : 'div' }}>
    @endforeach
</div>
</div>
