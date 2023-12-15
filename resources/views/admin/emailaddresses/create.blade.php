@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.users._breadcrumbs', [
        'user' => $email->user,
    ])
    <li class="breadcrumb-item active"><a href="{{ route('admin.users.emails.create', $email->user->id) }}">Add Email Address</a>
        @endsection

        @section('content')
            <div class="page-header mt-0">
                <h1>Add Email Address</h1>
            </div>

            <div class="col-md-6 offset-md-3">
                <form action="{{ route('admin.users.emails.store', $email->user->id) }}" method="post" class="card">
                    {{ csrf_field() }}
                    @include('admin.emailaddresses._form')
                </form>
            </div>
@endsection
