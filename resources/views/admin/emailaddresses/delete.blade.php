@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.users._breadcrumbs', [
        'user' => $email->user,
    ])
    <li class="breadcrumb-item active"><a href="{{ route('admin.users.emails.delete', $email->user->id) }}">Delete Email
            Address</a>
        @endsection

        @section('content')
            <div class="page-header mt-0">
                <h1>Delete {{ $email->email }}</h1>
            </div>

            <div class="col-md-6 offset-md-3">
                <form action="{{ route('admin.users.emails.destroy', [$email->user->id, $email->id]) }}" method="post"
                      class="card">
                    <div class="card-status-top bg-danger"></div>
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <div class="card-body text-center">
                        <i class="icon mb-4 ti ti-alert-triangle icon-lg text-danger"></i>
                        <p class="mt-4">
                            Are you sure you want to delete <strong>{{ $email->email }}</strong>?
                        </p>
                    </div>
                    <div class="card-footer text-end">
                        <div class="d-flex">
                            <a href="{{ route('admin.users.show', $email->user->id) }}" class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-danger ms-auto">Delete</button>
                        </div>
                    </div>
                </form>
            </div>
@endsection
