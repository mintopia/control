<div class="card-body">
    <div class="mb-3">
        <label class="form-label required">Address</label>
        <div>
            <input type="email" name="address" class="form-control @error('address') is-invalid @enderror"
                   placeholder="Email Address" value="{{ old('address', $email->email ?? '') }}">
            <small class="form-hint">The address</small>
            @error('address')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="verified" value="1"
                   @if(old('verified', $email->verified_at)) checked @endif>
            Verified
        </label>
    </div>
</div>
<div class="card-footer text-end">
    <div class="d-flex">
        <a href="{{ route('admin.users.show', $email->user->id) }}" class="btn btn-link">Cancel</a>
        <button type="submit" class="btn btn-primary ms-auto">Save</button>
    </div>
</div>
