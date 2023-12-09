@extends('layouts.app', [
    'activenav' => 'clans',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('clans.index') }}">Clans</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('clans.create') }}">Create Clan</a></li>
@endsection

@section('content')
    <div class="row g-2 align-items-center mb-4">
        <div class="col">
            <h2 class="page-title">Create Clan</h2>
        </div>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('clans.store') }}" method="post" class="card">
            {{ csrf_field() }}
            <div class="card-body">
                <h3 class="card-title">Name</h3>
                <p class="card-subtitle">Enter the name for your clan.</p>
                <input type="name" name="name" id="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Clan Name">
                @error('name')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('clans.index') }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Create</button>
                </div>
            </div>
        </form>
    </div>
@endsection
