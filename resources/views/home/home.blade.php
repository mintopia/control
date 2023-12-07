@extends('layouts.app', [
    'activenav' => 'home',
])

@section('breadcrumbs')
    <li class="breadcrumb-item active"><a href="{{ route('home') }}">Home</a></li>
@endsection

@section('content')
    <h1>Hello, World</h1>
@endsection
