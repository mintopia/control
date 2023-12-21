<div class="card-body">
    <div class="mb-3">
        <label class="form-label required">Name</label>
        <div>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   placeholder="My LAN Party" value="{{ old('name', $event->name ?? '') }}">
            <small class="form-hint">The name of the event. The code will be automatically generated based on the
                name</small>
            @error('name')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="draft" value="1"
                   @if(old('draft', $event->draft)) checked @endif>
            Draft
        </label>
    </div>
    <div class="mb-3">
        <label class="form-label required">Starts</label>
        <div>
            <input type="text" name="starts_at" class="form-control @error('starts_at') is-invalid @enderror"
                   placeholder="29th January"
                   value="{{ old('starts_at', ($event->starts_at ?? null) ? $event->starts_at->format('d M Y H:i') : '') }}">
            <small class="form-hint">The date and time the event starts. Accepts (almost) any date format</small>
            @error('starts_at')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label required">Ends</label>
        <div>
            <input type="text" name="ends_at" class="form-control @error('ends_at') is-invalid @enderror"
                   placeholder="31st January"
                   value="{{ old('ends_at', ($event->ends_at ?? null) ? $event->ends_at->format('d M Y H:i') : '') }}">
            <small class="form-hint">The date and time the event ends. Accepts (almost) any date format</small>
            @error('ends_at')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Box Office URL</label>
        <div>
            <input type="text" name="boxoffice_url" class="form-control @error('boxoffice_url') is-invalid @enderror"
                   placeholder="https://github.com/mintopia/control"
                   value="{{ old('boxoffice_url', $event->boxoffice_url ?? '') }}">
            <small class="form-hint">A URL that users can go to for buying a ticket</small>
            @error('boxoffice_url')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="seating_locked" value="1"
                   @if(old('seating_locked', $event->seating_locked)) checked @endif>
            Lock Seating
        </label>
    </div>
    <div class="mb-3">
        <label class="form-label">Seating Unlocks At</label>
        <div>
            <input type="text" name="seating_opens_at" class="form-control @error('seating_opens_at') is-invalid @enderror"
                   placeholder="29th January 4PM"
                   value="{{ old('seating_opens_at', ($event->seating_opens_at ?? null) ? $event->seating_opens_at->format('d M Y H:i') : '') }}">
            <small class="form-hint">The date and time that the seating plan unlocks. Accepts (almost) any date format</small>
            @error('seating_opens_at')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Seating Locks At</label>
        <div>
            <input type="text" name="seating_closes_at" class="form-control @error('seating_closes_at') is-invalid @enderror"
                   placeholder="29th January 4PM"
                   value="{{ old('seating_closes_at', ($event->seating_closes_at ?? null) ? $event->seating_closes_at->format('d M Y H:i') : '') }}">
            <small class="form-hint">The date and time that the seating plan locks. Accepts (almost) any date format</small>
            @error('seating_closes_at')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
