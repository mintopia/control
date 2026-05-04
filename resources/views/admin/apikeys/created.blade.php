@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.apikeys._breadcrumbs')
    <li class="breadcrumb-item active">Created</li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>API Key Created</h1>
    </div>

    @if(session()->has('apiKeyPlaintext'))
        <div class="col-md-8 offset-md-2">
            <div class="alert alert-warning">
                <h4 class="alert-title">Copy this key now</h4>
                <p>This is the only time the full key will ever be shown. After leaving this page, only the last four characters (<code>…{{ $apikey->last_four }}</code>) will be visible.</p>
            </div>

            <div class="card">
                <div class="card-body">
                    <label class="form-label">{{ $apikey->name }}</label>
                    <div class="input-group">
                        <input type="text" id="apikey-plaintext" class="form-control text-monospace"
                               value="{{ session('apiKeyPlaintext') }}" readonly>
                        <button type="button" class="btn btn-outline-primary" id="apikey-copy">
                            <i class="icon ti ti-copy"></i>
                            Copy
                        </button>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('admin.settings.apikeys.index') }}" class="btn btn-primary">Done</a>
                </div>
            </div>
        </div>

        @push('footer')
            <script>
                document.getElementById('apikey-copy').addEventListener('click', function () {
                    const input = document.getElementById('apikey-plaintext');
                    input.select();
                    navigator.clipboard.writeText(input.value);
                });
            </script>
        @endpush
    @else
        <div class="col-md-8 offset-md-2">
            <div class="alert alert-info">
                The plaintext key is no longer available. If you didn't capture it, delete this key and create a new one.
            </div>
            <a href="{{ route('admin.settings.apikeys.index') }}" class="btn btn-primary">Back to API Keys</a>
        </div>
    @endif
@endsection
