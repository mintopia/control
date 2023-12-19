@extends('layouts.app', [
    'activenav' => 'admin',
])

@section('breadcrumbs')
    @include('admin.clans._breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('admin.clans.edit', $clan->code) }}">Edit</a>
        @endsection

        @section('content')
            <div class="page-header mt-0">
                <h1>Edit {{ $clan->name }}</h1>
            </div>

            <div class="col-md-6 offset-md-3">
                <form action="{{ route('admin.clans.update', $clan->code) }}" method="post" class="card">
                    {{ csrf_field() }}
                    {{ method_field('PATCH') }}
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required">Name</label>
                            <div>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       placeholder="Name" value="{{ old('name', $clan->name ?? '') }}">
                                <small class="form-hint">The name of the clan. The code is automatically generated from
                                    the name.</small>
                                @error('name')
                                <p class="invalid-feedback">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <div class="d-flex">
                            <a href="{{ route('admin.clans.show', $clan->code) }}" class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-primary ms-auto">Save</button>
                        </div>
                    </div>
                </form>
            </div>
@endsection
