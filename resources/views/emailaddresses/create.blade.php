@extends('layouts.app', [
    'activenav' => 'profile',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('user.profile') }}">Profile</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('emails.create') }}">Add Email Address</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>Add Email Address</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('emails.store') }}" method="post" class="card">
            {{ csrf_field() }}
            <div class="card-body">
                <p>
                    Thank you for logging in. We just need to collect a few more bits of information.
                </p>
                <h3 class="card-title">Email Address</h3>
                <p class="card-subtitle">Enter your email address. We will send a verification code to it.</p>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror" placeholder="Email Address">
                @error('email')
                <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('user.profile') }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Send Verification Code</button>
                </div>
            </div>
        </form>
    </div>
@endsection
