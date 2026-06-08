@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.apikeys._breadcrumbs')
@endsection

@section('content')
    <div class="row g-2 align-items-center mb-4">
        <div class="col page-header mt-2">
            <h1>API Keys</h1>
        </div>
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="{{ route('admin.apikeys.create') }}" class="btn btn-primary d-inline-block">
                    <i class="icon ti ti-plus"></i>
                    New API Key
                </a>
            </div>
        </div>
    </div>

    @if(session()->has('apiKeyPlaintext'))
        <div class="alert alert-warning alert-dismissible mb-4" role="alert">
            <h4 class="alert-title">Copy this key now</h4>
            <p>This is the only time the full key will ever be shown. After leaving this page, only the last four characters (<code>…{{ session('apiKeyLastFour') }}</code>) will be visible.</p>
            <label class="form-label">{{ session('apiKeyName') }}</label>
            <div class="input-group">
                <input type="text" id="apikey-plaintext" class="form-control text-monospace"
                       value="{{ session('apiKeyPlaintext') }}" readonly>
                <button type="button" class="btn btn-outline-primary" id="apikey-copy">
                    <i class="icon ti ti-copy"></i>
                    Copy
                </button>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>

        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
            <div id="apikey-copied-toast" class="toast fade text-bg-success border-0" role="status" aria-live="polite" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="icon ti ti-check me-2"></i>
                        Copied to clipboard
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" aria-label="Close" id="apikey-copied-toast-close"></button>
                </div>
            </div>
        </div>

        @push('footer')
            <script>
                (function () {
                    const copyBtn = document.getElementById('apikey-copy');
                    const input = document.getElementById('apikey-plaintext');
                    const toastEl = document.getElementById('apikey-copied-toast');
                    if (!copyBtn || !input || !toastEl) {
                        return;
                    }
                    const closeBtn = document.getElementById('apikey-copied-toast-close');
                    let hideTimer;
                    const showToast = () => {
                        clearTimeout(hideTimer);
                        toastEl.classList.add('show');
                        hideTimer = setTimeout(() => toastEl.classList.remove('show'), 2500);
                    };
                    copyBtn.addEventListener('click', async () => {
                        try {
                            await navigator.clipboard.writeText(input.value);
                        } catch (e) {
                            input.focus();
                            input.select();
                            document.execCommand('copy');
                        }
                        showToast();
                    });
                    closeBtn?.addEventListener('click', () => {
                        clearTimeout(hideTimer);
                        toastEl.classList.remove('show');
                    });
                })();
            </script>
        @endpush
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Key</th>
                        <th class="w-1">Status</th>
                        <th>Last Used</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($apikeys as $apikey)
                        <tr>
                            <td>{{ $apikey->name }}</td>
                            <td><code>ctrl_…{{ $apikey->last_four }}</code></td>
                            <td>
                                @if($apikey->enabled)
                                    <span class="status status-green">Enabled</span>
                                @else
                                    <span class="status status-muted">Disabled</span>
                                @endif
                            </td>
                            <td>
                                {{ $apikey->last_used_at?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td>{{ $apikey->created_at->format('Y-m-d') }}</td>
                            <td>
                                <div class="btn-list justify-content-end">
                                    <a href="{{ route('admin.apikeys.edit', $apikey->id) }}" class="btn btn-outline-primary">
                                        <i class="icon ti ti-edit"></i>
                                        Edit
                                    </a>
                                    <a href="{{ route('admin.apikeys.delete', $apikey->id) }}" class="btn btn-outline-danger">
                                        <i class="icon ti ti-trash"></i>
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No API keys yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($apikeys->hasPages())
            <div class="card-footer">
                {{ $apikeys->links() }}
            </div>
        @endif
    </div>
@endsection
