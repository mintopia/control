@if(array_key_exists($fieldName, $config))
    <div class="mb-3">
        <label class="form-label @if(strpos($config[$fieldName]->validation, 'required') !== false) required @endif">{{ $config[$fieldName]->name }}</label>
        <div>
            <input type="text" name="{{ $fieldName }}" class="form-control @error($fieldName) is-invalid @enderror" value="{{ old($fieldName, $provider->{$fieldName} ?? '') }}">
            @error($fieldName)
            <p class="invalid-feedback">{{ $message }}</p>
            @enderror
        </div>
    </div>
@endif
