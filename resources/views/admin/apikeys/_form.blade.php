<div class="card-body">
    <div class="mb-3">
        <label class="form-label required">Name</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               placeholder="Name" value="{{ old('name', $apikey->name ?? '') }}">
        <small class="form-hint">A human-readable label so you can tell keys apart.</small>
        @error('name')
            <p class="invalid-feedback">{{ $message }}</p>
        @enderror
    </div>

    @if($apikey->exists)
        <div class="mb-3">
            <label class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="enabled" value="1"
                       @if(old('enabled', $apikey->enabled)) checked @endif>
                Enabled
            </label>
            <small class="form-hint">Disable to immediately revoke this key without deleting it.</small>
        </div>
    @endif
</div>
