<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Event</label>
        <div class="form-control-plaintext">{{ $ticket->event->name }}</div>
    </div>
    <div class="mb-3">
        <label class="form-label required">Reference</label>
        <div>
            <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror" placeholder="abc123" value="{{ old('reference', $ticket->reference ?? '') }}">
            <small class="form-hint">A unique booking reference for this ticket</small>
            @error('reference')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label required">Ticket Type</label>
        <select class="form-select @error('ticket_type_id') is-invalid @enderror" name="ticket_type_id">
            @foreach($ticket->event->ticketTypes as $type)
                <option value="{{ $type->id }}" @if(old('ticket_type_id', $ticket->ticket_type_id) == $type->id) selected @endif>{{ $type->name }}</option>
            @endforeach
        </select>
        <div>
            <small class="form-hint">The type of ticket</small>
            @error('ticket_type_id')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label required">User ID</label>
        <div>
            <input type="text" name="user_id" class="form-control @error('user_id') is-invalid @enderror" placeholder="42" value="{{ old('user_id', $ticket->user_id) }}">
            <small class="form-hint">The ID of the user that the ticket is assigned to</small>
            @error('user_id')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
