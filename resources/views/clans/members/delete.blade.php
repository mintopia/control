@extends('layouts.app', [
    'activenav' => 'clans',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('clans.index') }}">Clans</a></li>
    <li class="breadcrumb-item"><a href="{{ route('clans.show', $clan->code) }}">{{ $clan->name }}</a></li>
    <li class="breadcrumb-item active">
        <a href="{{ route('clans.members.delete', [$clan->code, $member->id]) }}">
            @if ($leave)
                Leave
            @else
                Remove {{ $member->user->nickname }}
            @endif
        </a>
    </li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>
            @if ($leave)
                Leave {{ $clan->name }}
            @else
                Remove {{ $member->user->nickname }}
            @endif
        </h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('clans.members.destroy', [$clan->code, $member->id]) }}" method="post" class="card">
            <div class="card-status-top bg-danger"></div>
            {{ csrf_field() }}
            {{ method_field('DELETE') }}
            <div class="card-body text-center">
                <i class="icon mb-4 ti ti-alert-triangle icon-lg text-danger"></i>
                <p class="mt-4">
                    @if ($leave)
                        Are you sure you want to leave <strong>{{ $clan->name }}</strong>?
                    @else
                        Are you sure you want to remove <strong>{{ $member->user->nickname }}</strong> from <strong>{{ $clan->name }}</strong>?
                    @endif
                </p>
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('clans.show', $clan->code) }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-danger ms-auto">{{ $leave ? 'Leave' : 'Remove' }}</button>
                </div>
            </div>
        </form>
    </div>
@endsection
