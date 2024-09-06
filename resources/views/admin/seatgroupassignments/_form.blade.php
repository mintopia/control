<div class="card-body">
    <div class="mb-3">
        <label class="form-label required">Assignment Type</label>
        <select class="form-control" name="assignment_type">
            <option value="user" selected>User</option>
            <option value="clan">Clan</option>
            <option value="ticket_type">Ticket Type</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label required">Type ID</label>
        <div>
            <input type="text" name="assignment_type_id" class="form-control @error('assignment_type_id') is-invalid @enderror"
                   placeholder="0" value="{{ old('name', $assignment->assignment_type_id ?? '') }}">
            <small class="form-hint">The ID for the selected assignment type</small>
            @error('assignment_type_id')
                <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
