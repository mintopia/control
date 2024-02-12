<div class="card-body">
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label required">X</label>
            <div>
                <input type="text" name="x" class="form-control @error('x') is-invalid @enderror" placeholder="1"
                       value="{{ old('x', $seat->x ?? '') }}">
                <small class="form-hint">Horizontal position for this seat, from the left</small>
                @error('x')
                <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label required">Y</label>
            <div>
                <input type="text" name="y" class="form-control @error('y') is-invalid @enderror" placeholder="1"
                       value="{{ old('y', $seat->y ?? '') }}">
                <small class="form-hint">Vertical position for this seat, from the top</small>
                @error('y')
                <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label required">Row</label>
            <div>
                <input type="text" name="row" class="form-control @error('row') is-invalid @enderror" placeholder="A"
                       value="{{ old('row', $seat->row ?? '') }}">
                <small class="form-hint">The row for this seat</small>
                @error('row')
                <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label required">Number</label>
            <div>
                <input type="text" name="number" class="form-control @error('number') is-invalid @enderror"
                       placeholder="42" value="{{ old('number', $seat->number ?? '') }}">
                <small class="form-hint">The number for this seat</small>
                @error('number')
                <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label required">Label</label>
        <div>
            <input type="text" name="label" class="form-control @error('label') is-invalid @enderror" placeholder="A42"
                   value="{{ old('label', $seat->label ?? '') }}">
            <small class="form-hint">The label to display for this seat, usually {row}{number}</small>
            @error('label')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Description</label>
        <div>
            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                   placeholder="Staff" value="{{ old('description', $seat->description ?? '') }}">
            <small class="form-hint">An optional description to display along with the seat</small>
            @error('description')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">CSS Class</label>
        <div>
            <input type="text" name="class" class="form-control @error('class') is-invalid @enderror"
                   placeholder="my-class" value="{{ old('class', $seat->class ?? '') }}">
            <small class="form-hint">An optional CSS class to apply to this seat</small>
            @error('class')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Assigned Clan</label>
        <div>
            <select class="form-select @error('clan_id') is-invalid @enderror" name="clan_id">
                <option value=""></option>
                @foreach($clans as $clan)
                <option value="{{ $clan->id }}"
                        @if(old('clan_id', $seat->clan_id) == $clan->id) selected @endif>{{ $clan->name }}</option>
                @endforeach
            </select>
            <small class="form-hint">An optional Clan to assign to this seat.</small>
            @error('clan_id')
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="disabled" value="1"
                   @if(old('disabled', $seat->disabled)) checked @endif>
            Disabled
        </label>
    </div>
</div>
