@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Settings</h1>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Social Providers</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th class="w-1">Supports Auth</th>
                    <th class="w-1">Enabled</th>
                    <th class="w-1">Auth Enabled</th>
                    <th>Return URLs</th>
                    <th class="w-1"></th>
                </tr>
                </thead>
                <tbody>
                @forelse($socialProviders as $provider)
                    <tr>
                        <td>
                            {{ $provider->name }}
                            <br />
                            <span class="text-muted">{{ $provider->code }}</span>
                        </td>
                        <td>
                            @if($provider->supports_auth)
                                <span class="status status-green">
                                    Yes
                                </span>
                            @else
                                <span class="status status-muted">
                                    No
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($provider->enabled)
                                <span class="status status-green">
                                    Yes
                                </span>
                            @else
                                <span class="status status-red">
                                    No
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($provider->auth_enabled)
                                <span class="status status-green">
                                    Yes
                                </span>
                            @elseif($provider->supports_auth)
                                <span class="status status-red">
                                    No
                                </span>
                            @else
                                <span class="status status-muted">
                                    N/A
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="user-select-all">{{ route('login.return', $provider->code) }}</span><br />
                            <span class="user-select-all">{{ route('linkedaccounts.store', $provider->code) }}</span>
                        </td>
                        <td>
                            <div class="btn-list justify-content-end">
                                <a href="{{ route('admin.settings.socialproviders.edit', $provider->id) }}"
                                   class="btn btn-outline-primary">
                                    <i class="icon ti ti-edit"></i>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center p-4">
                            <p>There are no social providers</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ticket Providers</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Enabled</th>
                    <th>Webhook URL</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($ticketProviders as $provider)
                    <tr>
                        <td>
                            {{ $provider->name }}
                            <br />
                            <span class="text-muted">{{ $provider->code }}</span>
                        </td>
                        <td>
                            @if($provider->enabled)
                                <span class="status status-green">
                                        Yes
                                    </span>
                            @else
                                <span class="status status-red">
                                        No
                                    </span>
                            @endif
                        </td>
                        <td>
                            <span class="user-select-all">{{ route('webhooks.tickets', $provider->code) }}</span>
                        </td>
                        <td>
                            <div class="btn-list justify-content-end">
                                <a href="{{ route('admin.settings.ticketproviders.clearcache', $provider->id) }}"
                                   class="btn btn-outline-primary">
                                    <i class="icon ti ti-refresh"></i>
                                    Clear Cache
                                </a>
                                <a href="{{ route('admin.settings.ticketproviders.edit', $provider->id) }}"
                                   class="btn btn-outline-primary">
                                    <i class="icon ti ti-edit"></i>
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center p-4">
                            <p>There are no ticket providers</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
