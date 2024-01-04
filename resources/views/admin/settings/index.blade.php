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

    <div class="row">
        @if(count($settings) > 0)
            <div class="col-12 col-lg-6">
                <form action="{{ route('admin.settings.update') }}" method="post" class="card mb-4">
                    {{ csrf_field() }}
                    {{ method_field('PATCH') }}
                    <div class="card-body">
                        @foreach($settings as $setting)
                            @if($setting->type === \App\Enums\SettingType::stString)
                                <div class="mb-3">
                                    <label class="form-label @if(str_contains($setting->validation ?? '', 'required')) required @endif ">{{ $setting->name }}</label>
                                    <div>
                                        <input type="text" name="{{ $setting->code }}" class="form-control @error($setting->code) is-invalid @enderror"
                                               value="{{ old($setting->name, $setting->value ?? '') }}">
                                        @if($setting->description)
                                            <small class="form-hint">{{ $setting->description }}</small>
                                        @endif
                                        @error($setting->code)
                                        <p class="invalid-feedback">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @elseif($setting->type === \App\Enums\SettingType::stBoolean)
                                <div class="mb-3">
                                    <label class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" name="{{ $setting->code }}" value="1"
                                               @if(old($setting->name, $setting->value)) checked @endif>
                                        {{ $setting->name }}
                                    </label>
                                    @if($setting->description)
                                        <small class="form-hint">{{ $setting->description }}</small>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="card-footer text-end">
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary ms-auto">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
        <div class="col-12 col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Themes</h3>

                    <a class="btn btn-primary ms-auto" href="{{ route('admin.settings.themes.create') }}">
                        <i class="icon ti ti-pencil-plus"></i>
                        Add Theme
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th class="w-1">Active</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($themes as $theme)
                            <tr>
                                <td>
                                    {{ $theme->name }}
                                </td>
                                <td>
                                    @if($theme->active)
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
                                    <div class="btn-list justify-content-end">
                                        <a href="{{ route('admin.settings.themes.edit', $theme->id) }}"
                                           class="btn btn-outline-primary">
                                            <i class="icon ti ti-edit"></i>
                                            Edit
                                        </a>
                                        <a class="btn btn-outline-danger @if($theme->readonly) disabled @endif"
                                           @if (!$theme->readonly) href="{{ route('admin.settings.themes.delete', $theme->id) }}" @endif>
                                            <i class="icon ti ti-trash"></i>
                                            Delete
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
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Discord Integration</h3>
                </div>
                @if($discordProvider && $discordProvider->client_id && $discordProvider->client_secret && $discordProvider->token)
                    @if($discordId && $discordName && $discordId->value && $discordName->value)
                        <div class="card-body">
                            <div class="datagrid mb-3">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Server Name</div>
                                    <div class="datagrid-content">{{ $discordName->value }}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Server ID</div>
                                    <div class="datagrid-content">{{ $discordId->value }}</div>
                                </div>
                            </div>

                            <p>
                                To assign a role to users, the bot's role needs to be <strong>above</strong> the role it needs to assign in the roles list.
                            </p>

                            <p>
                                If you have removed the bot from your server and need to reconnect it, you can do that here.
                            </p>
                        </div>
                        <div class="card-footer d-flex">
                            <a class="btn btn-discord ms-auto" href="{{ route('admin.settings.discord') }}">
                                <i class="icon ti ti-brand-discord"></i>
                                Reconnect to Discord Server
                            </a>
                        </div>
                    @else
                        <div class="card-body">
                            <p>
                                To integrate with your Discord server and be able to automatically assign roles to users based
                                on their event tickets, just connect to your Discord server here.
                            </p>
                        </div>
                        <div class="card-footer d-flex">
                            <a class="btn btn-discord ms-auto" href="{{ route('admin.settings.discord') }}">
                                <i class="icon ti ti-brand-discord"></i>
                                Connect to Discord Server
                            </a>
                        </div>
                    @endif
                @else
                    <div class="card-body">
                        <p>
                            If you configure the Discord social provider, you can add the Seat Picker to your Discord server. It
                            will then be able to add and remove roles to users based on their event tickets.
                        </p>
                    </div>
                @endif
            </div>
        </div>
        <div class="col col-12">
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
                                <br/>
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
                                <span class="user-select-all">{{ route('login.return', $provider->code) }}</span><br/>
                                <span class="user-select-all">{{ route('linkedaccounts.store', $provider->code) }}</span>
                                @if ($provider->code === 'discord')
                                    <br/>
                                    <span class="user-select-all">{{ route('admin.settings.discord_return') }}</span>
                                @endif
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
        </div>
        <div class="col col-12">
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
                                <br/>
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
                                    <a href="{{ route('admin.settings.ticketproviders.sync', $provider->id) }}"
                                       class="btn btn-outline-primary">
                                        <i class="icon ti ti-refresh-alert"></i>
                                        Sync Tickets
                                    </a>
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
        </div>
    </div>
@endsection
