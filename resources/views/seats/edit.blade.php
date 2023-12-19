@extends('layouts.app', [
    'activenav' => 'seating',
])

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('seatingplans.index') }}">Seating Plans</a></li>
    <li class="breadcrumb-item active"><a
            href="{{ route('seatingplans.show', $seat->plan->event->code) }}">{{ $seat->plan->event->name }}</a></li>
    <li class="breadcrumb-item active"><a href="{{ route('seats.edit', $seat->id) }}">{{ $seat->label }}</a></li>
@endsection

@section('content')
    <div class="page-header mt-0">
        <h1>{{ $seat->label }}</h1>
    </div>

    <div class="col-md-6 offset-md-3">
        <form action="{{ route('seats.update', $seat->id) }}" method="post" class="card">
            {{ csrf_field() }}
            {{ method_field('PATCH') }}
            <div class="card-body">
                <h3 class="card-title">Ticket</h3>
                @if ($seat->ticket)
                    <p>
                        This seat is already occupied by {{ $seat->ticket->user->nickname }}. Do you want to unseat or
                        swap them?
                    </p>
                    <div class="mb-3">
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="swap" value="0" checked>
                            <span class="form-check-label">Unseat</span>
                        </label>
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="swap" value="1">
                            <span class="form-check-label">Swap</span>
                        </label>
                    </div>
                @endif
                <p>Select the ticket to assign to {{ $seat->label }}</p>

                @error('ticket_id')
                <p class="invalid-feedback">{{ $message }}</p>
                @enderror
                <p>
                    @php($checked = false)
                    @foreach($tickets as $ticket)
                        @if($ticket->seat && $ticket->seat->id === $seat->id)
                            @continue
                        @endif
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="ticket_id" value="{{ $ticket->id }}"
                                   @if(!$checked) checked @endif>
                            <span class="form-check-label">
                                {{ $ticket->user->nickname }}

                                @if ($ticket->seat)
                                    <span class="badge">{{ $ticket->seat->label }}</span>
                                @endif
                            </span>
                        </label>
                        @php($checked = true)
                    @endforeach
                </p>
            </div>
            <div class="card-footer text-end">
                <div class="d-flex">
                    <a href="{{ route('seatingplans.show', $seat->plan->event->code) }}" class="btn btn-link">Cancel</a>
                    <button type="submit" class="btn btn-primary ms-auto">Choose Seat</button>
                </div>
            </div>
        </form>
    </div>
@endsection
