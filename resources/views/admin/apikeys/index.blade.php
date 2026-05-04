@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.apikeys._breadcrumbs')
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>API Keys</h1>
        <div class="ms-auto">
            <a href="{{ route('admin.settings.apikeys.create') }}" class="btn btn-primary">
                <i class="icon ti ti-plus"></i>
                New API Key
            </a>
        </div>
    </div>

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
                                    <a href="{{ route('admin.settings.apikeys.edit', $apikey->id) }}" class="btn btn-outline-primary">
                                        <i class="icon ti ti-edit"></i>
                                    </a>
                                    <a href="{{ route('admin.settings.apikeys.delete', $apikey->id) }}" class="btn btn-outline-danger">
                                        <i class="icon ti ti-trash"></i>
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
