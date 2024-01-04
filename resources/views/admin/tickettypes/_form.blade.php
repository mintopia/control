<div class="card-body">
    <div class="mb-3">
        <label class="form-label required">Name</label>
        <div>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   placeholder="Participant" value="{{ old('name', $type->name ?? '') }}">
            <small class="form-hint">The name of the ticket type</small>
            @error('name')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="has_seat" value="1"
                   @if(old('has_seat', $type->has_seat)) checked @endif>
            This ticket has a seat
        </label>
    </div>

    <div class="mb-3">
        <label class="form-label">Discord Role</label>
        <select class="form-select @error('discord_role_id') is-invalid @enderror" name="discord_role_id">
            @foreach($roles as $id => $role)
                <option value="{{ $id }}"
                        @if(old('discord_role_id', $type->discord_role_id) == $id) selected @endif>{{ $role }}</option>
            @endforeach
        </select>
        <div>
            <small class="form-hint">A Discord role that ticket-holders will be allocated</small>
            @error('discord_role_id')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
