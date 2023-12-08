@extends('layouts.app', [
    'activenav' => 'user.profile',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('user.profile') }}">Profile</a></li>
@endsection

@section('content')
    <div class="row g-2 align-items-center mb-4">
        <div class="col">
            <h2 class="page-title">Profile</h2>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <h3>Linked Accounts</h3>
        </div>
        @foreach(Auth::user()->accounts as $account)
            <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="ribbon ribbon-top bg-{{ $account->provider->code }}">
                    <i class="icon ti ti-brand-{{ $account->provider->code }}-filled"></i>
                </div>
                <div class="row row-0">
                    <div class="col-3">
                        <img src="{{ $account->avatar_url }}" class="w-100 h-100 object-cover card-img-start" alt="{{ $account->provider->name }} Avatar">
                    </div>
                    <div class="col">
                        <div class="card-body p-2">
                            <h3 class="card-title">{{ $account->provider->name }}</h3>
                            <p class="text-secondary">
                                {{ $account->name }}<br />
                                {{ $account->email->email }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col">
            <h3>Email Addresses</h3>
        </div>
    </div>
    <div class="row mb-2">
        <div class="col">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>Address</th>
                            <th>Verified</th>
                            <th>Linked Accounts</th>
                            <th class="w-1"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach(Auth::user()->emails as $email)
                            <tr>
                                <td @if (!$email->verified_at) class="text-secondary"@endif>
                                    {{ $email->email }}
                                    @if ($email->id === Auth::user()->primaryEmail->id)
                                        <i class="icon ti ti-star-filled text-yellow" title="Primary Email Address"></i>
                                    @endif
                                </td>
                                <td>
                                    @if($email->verified_at)
                                        Yes
                                    @else
                                        <a href="{{ route('emails.verify', $email->id) }}" class="text-red">No - Verify Now</a>
                                    @endif
                                </td>
                                <td>
                                    <span class="badges-list">
                                        @foreach($email->linkedAccounts as $account)
                                            <span class="badge bg-{{ $account->provider->code }} text-{{ $account->provider->code }}-fg">{{ $account->provider->name }}</span>
                                        @endforeach
                                    </span>
                                </td>
                                <td>
                                    @if ($email->canDelete())
                                        <a href="{{ route('emails.delete', $email->id) }}">Remove</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 align-items-center">
        <div class="col-auto ms-auto d-print-none">
            <div class="btn-list">
                <a href="{{ route('emails.create') }}" class="btn d-none d-sm-inline-block">
                    <i class="icon ti ti-mail-plus"></i>
                    Add Email Address
                </a>
            </div>
        </div>
    </div>
@endsection
