@extends('layouts.app', [
    'activenav' => 'profile',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('user.profile') }}">Profile</a></li>
    <li class="breadcrumb-item"><a href="{{ route('user.profile') }}">{{ $email->email }}</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('emails.create') }}">Verification</a></li>
@endsection

@section('content')
    <div class="row g-2 align-items-center mb-4">
        <div class="col">
            <h2 class="page-title">Verify Email Address</h2>
        </div>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('emails.verify', $email->id) }}" method="post" class="card">
            {{ csrf_field() }}
            <div class="card-body">
                <h3 class="card-title">Code</h3>
                <p class="card-subtitle">Enter the verification code that we sent you.</p>
                <input type="text" name="code" id="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" placeholder="Verification Code">
                @error('code')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
                <p class="text-secondary mt-3">
                    If you haven't received a code, we can <a href="{{ route('emails.verify.resend', $email->id) }}">send you a new one</a>.
                </p>
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('user.profile') }}" class="btn btn-link">Cancel</a>
                    <button name="verify" type="submit" class="btn btn-primary ms-auto">Verify</button>
                </div>
            </div>
        </form>
    </div>
@endsection
