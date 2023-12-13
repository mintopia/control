@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('admin.users.show', $user->id) }}">{{ $user->nickname }}</a>
    </li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>{{ $user->nickname }}</h1>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">ID</div>
                            <div class="datagrid-content">{{ $user->id }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Nickname</div>
                            <div class="datagrid-content">{{ $user->nickname }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Name</div>
                            <div class="datagrid-content">{{ $user->name }}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Primary Email</div>
                            <div class="datagrid-content">
                                @if($user->primaryEmail)
                                    <a href="mailto:{{ $user->primaryEmail->email }}">{{ $user->primaryEmail->email }}</a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Roles</div>
                            <div class="datagrid-content">
                                @forelse($user->roles as $role)
                                    <span class="badge bg-primary text-primary-fg">{{ $role->name }}
                                        @empty
                                            <span class="text-muted">None</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Completed Signup?</div>
                            <div class="datagrid-content">
                                @if($user->first_login)
                                    <span class="status status-red">
                                        No
                                    </span>
                                @else
                                    <span class="status status-green">
                                        Yes
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Agreed Terms and Conditions</div>
                            <div class="datagrid-content">
                                @if($user->terms_agreed_at)
                                    <span class="status status-green">
                                        Yes
                                    </span>
                                @else
                                    <span class="status status-red">
                                        No
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Created</div>
                            <div class="datagrid-content">
                                <span title="{{ $user->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $user->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer align-content-end d-flex btn-list">
                    <a href="{{ route('admin.users.delete', $user->id) }}" class="btn btn-outline-danger">
                        <i class="icon ti ti-trash"></i>
                        Delete
                    </a>
                    <a href="{{ route('admin.users.impersonate', $user->id) }}"
                       class="btn btn-primary-outline ms-auto">
                        <i class="icon ti ti-spy"></i>
                        Impersonate
                    </a>
                    <a href="{{ route('admin.tickets.index', ['user_id' => $user->id]) }}"
                       class="btn btn-primary-outline">
                        <i class="icon ti ti-ticket"></i>
                        Tickets
                    </a>
                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary">
                        <i class="icon ti ti-edit"></i>
                        Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <h2>Linked Accounts</h2>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Provider</th>
                            <th>Nickname</th>
                            <th>External ID</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($user->accounts as $account)
                            <tr>
                                <td class="text-muted">{{ $account->id }}</td>
                                <td>{{ $account->provider->name }}</td>
                                <td>
                                    <div class="d-flex py-1 align-items-center">
                                        <span class="avatar me-2"
                                              style="background-image: url('{{ $account->avatar_url }}')"></span>
                                        <div class="flex-fill">
                                            <div class="font-weight-medium">{{ $account->name }}</div>
                                            <div class="text-secondary">{{ $account->email->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $account->external_id }}</td>
                                <td></td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-4">
                                    <p>There are no linked accounts</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <h2>Email Addresses</h2>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Address</th>
                            <th>Verified</th>
                            <th>Linked Accounts</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($user->emails as $email)
                            <tr>
                                <td class="text-muted">{{ $email->id }}</td>
                                <td>
                                    {{ $email->email }}
                                    @if($email->id === $user->primaryEmail->id)
                                        <i class="icon ti ti-star-filled text-yellow"></i>
                                    @endif
                                </td>
                                <td>
                                    @if($email->verified_at)
                                        <span title="{{ $email->verified_at->format('Y-m-d H:i:s') }}">{{ $email->verified_at->diffForHumans() }}</span>
                                    @else
                                        <span class="status status-red">No</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badges-list">
                                        @foreach($email->linkedAccounts as $account)
                                            <span class="badge bg-{{ $account->provider->code }} text-{{ $account->provider->code }}-fg">{{ $account->provider->name }}</span>
                                        @endforeach
                                    </span>
                                </td>
                                <td></td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="5" class="text-center p-4">
                                    <p>There are no email addresses</p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <h2>Clan Memberships</h2>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($user->clanMemberships as $clanMember)
                            <tr>
                                <td class="text-muted">{{ $clanMember->id }}</td>
                                <td>
                                    {{ $clanMember->clan->name }}
                                </td>
                                <td>{{ $clanMember->role->name }}</td>
                                <td>
                                    <span title="{{ $clanMember->created_at->format('Y-m-d H:i:s') }}">{{ $clanMember->created_at->diffForHumans() }}</span>
                                </td>
                                <td></td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="5" class="text-center p-4">
                                    <p>The user isn't in any clans</p>
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
