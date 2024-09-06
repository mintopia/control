<div class="card-body">
    <div class="mb-3">
        <label class="form-label required">Name</label>
        <div>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   placeholder="Name" value="{{ old('name', $group->name ?? '') }}">
            <small class="form-hint">The name of the seat group</small>
            @error('name')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">CSS Class</label>
        <div>
            <input type="text" name="class" class="form-control @error('class') is-invalid @enderror"
                   placeholder="my-class" value="{{ old('class', $group->class ?? '') }}">
            <small class="form-hint">An optional CSS class to apply to this seat group assignment</small>
            @error('class')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
