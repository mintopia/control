@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
    <li class="breadcrumb-item active"><a
            href="{{ route('admin.settings.ticketproviders.edit', $provider->id) }}">Edit {{ $provider->name }}</a>
        @endsection

        @section('content')
            <div class="page-header mt-0">
                <h1>Edit {{ $provider->name }}</h1>
            </div>

            <div class="col-md-6 offset-md-3">
                <form action="{{ route('admin.settings.ticketproviders.update', $provider->id) }}" method="post"
                      class="card">
                    {{ csrf_field() }}
                    {{ method_field('PATCH') }}
                    <div class="card-body">
                        @include('partials._providersconfig', [
                            'fieldName' => 'endpoint',
                        ])
                        @include('partials._providersconfig', [
                            'fieldName' => 'apikey',
                        ])
                        @include('partials._providersconfig', [
                            'fieldName' => 'apisecret',
                        ])
                        @include('partials._providersconfig', [
                            'fieldName' => 'webhook_secret',
                        ])
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="enabled" value="1"
                                       @if(old('disabled', $provider->enabled)) checked @endif>
                                Enabled
                            </label>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <div class="d-flex">
                            <a href="{{ route('admin.settings.index') }}" class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-primary ms-auto">Save</button>
                        </div>
                    </div>
                </form>
            </div>
@endsection
