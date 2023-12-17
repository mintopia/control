<div class="card-body">
    <div class="mb-3">
        <label class="form-label required">Name</label>
        <div>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="Participant" value="{{ old('name', $type->name ?? '') }}">
            <small class="form-hint">The name of the ticket type</small>
            @error('name')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="has_seat" value="1" @if(old('has_seat', $type->has_seat)) checked @endif>
            This ticket has a seat
        </label>
    </div>
</div>
