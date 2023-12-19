<div class="card-body">
    @foreach($availableMappings as $available)
        <h3 class="card-title">{{ $available->provider->name }}</h3>
        <div>
            @foreach($available->events as $i => $event)
                @php
                    $checked = false;
                    $key = "{$available->provider->id}:{$event->id}";
                    if (old('external_id') === $key) {
                        $checked = true;
                    } elseif ($mapping->ticket_provider_id === $available->provider->id && $mapping->external_id && $mapping->external_id === $event->id) {
                        $checked = true;
                    } elseif ($i === 0) {
                        $checked = true;
                    }
                @endphp
                <label class="form-check">
                    <input class="form-check-input" type="radio" name="external_id" value="{{ $key }}"
                           @if($checked) checked @endif
                    <span class="form-check-label">
                        {{ $event->name }}
                        @if ($event->id == $mapping->external_id)
                            <span class="badge bg-primary text-primary-fg">Current</span>
                        @endif
                    </span>
                </label>
            @endforeach
            @error('external_id')
            <p class="invalid-feedback d-block">{{ $message }}</p>
            @enderror
        </div>
    @endforeach
</div>
