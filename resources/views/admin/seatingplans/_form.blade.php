<div class="card-body">
    <div class="mb-3">
        <label class="form-label required">Name</label>
        <div>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   placeholder="Sandford Village Hall" value="{{ old('name', $plan->name ?? '') }}">
            <small class="form-hint">The name of the seating plan. The code will be automatically generated based on the
                name</small>
            @error('name')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Background Image URL</label>
        <div>
            <input type="text" name="image_url" class="form-control @error('image_url') is-invalid @enderror"
                   placeholder="https://placekitten.com/400x100" value="{{ old('image_url', $plan->image_url ?? '') }}">
            <small class="form-hint">A background image to display behind the seating plan</small>
            @error('image_url')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Scale</label>
        <div>
            <div class="input-group">
                <input type="text" name="scale" class="form-control @error('scale') is-invalid @enderror"
                       placeholder="100" value="{{ old('scale', $plan->scale ?? '') }}">
                <div class="input-group-prepend">
                    <div class="input-group-text">%</div>
                </div>
            </div>
            <small class="form-hint">The scale of the seating plan grid, expressed as a percentile. Valid range is 25-200.</small>
            @error('scale')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
