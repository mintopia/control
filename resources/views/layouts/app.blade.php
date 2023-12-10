<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        {{ config('app.name') }}
    </title>
    @stack('head')
</head>
<body>
<div class="page">
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <h1 class="navbar-brand navbar-brand-autodark mt-1">
                <a href="{{ route('home') }}">
                    {{ config('app.name') }}
                </a>
            </h1>


            <div class="collapse navbar-collapse" id="sidebar-menu">
                <ul class="navbar-nav pt-lg-3">
                    <li class="nav-item @if(($activenav ?? null) === 'home') active @endif">
                        <a class="nav-link" href="{{ route('home') }}" >
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="icon ti ti-home"></i>
                            </span>
                            <span class="nav-link-title">
                                Home
                            </span>
                        </a>
                    </li>
                    <li class="nav-item @if(($activenav ?? null) === 'tickets') active @endif">
                        <a class="nav-link" href="{{ route('tickets.index') }}" >
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="icon ti ti-ticket"></i>
                            </span>
                            <span class="nav-link-title">
                                Tickets
                            </span>
                        </a>
                    </li>
                    <li class="nav-item @if(($activenav ?? null) === 'seatingplans') active @endif">
                        <a class="nav-link" href="#" >
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="icon ti ti-armchair"></i>
                            </span>
                            <span class="nav-link-title">
                                Seating Plans
                            </span>
                        </a>
                    </li>
                    <li class="nav-item @if(($activenav ?? null) === 'clans') active @endif">
                        <a class="nav-link" href="{{ route('clans.index') }}" >
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="icon ti ti-users-group"></i>
                            </span>
                            <span class="nav-link-title">
                                Clans
                            </span>
                        </a>
                    </li>
                    <li class="nav-item @if(($activenav ?? null) === 'profile') active @endif">
                        <a class="nav-link" href="{{ route('user.profile') }}" >
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="icon ti ti-user"></i>
                            </span>
                            <span class="nav-link-title">
                                Profile
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </aside>

    <div class="page-wrapper">
        <header class="navbar d-print-none">
            <div class="container-xl">
                <div class="navbar-nav flex-row">
                    <div class="d-flex p-2">
                        <ol class="breadcrumb" aria-label="breadcrumbs">
                            @yield('breadcrumbs')
                        </ol>
                    </div>
                </div>

                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                        <span class="avatar avatar-sm" style="background-image: url('{{ Auth::user()->avatarUrl() }}');"></span>
                        <div class="d-none d-xl-block ps-2">
                            <div>{{ Auth::user()->nickname }}</div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <a href="{{ route('user.profile') }}" class="dropdown-item">Profile</a>
                        <a href="{{ route('logout') }}" class="dropdown-item">Logout</a>
                    </div>
                </div>
            </div>
        </header>
        @yield('header')
        <div class="page-body">
            <div class="container-xl">
                @if (session('successMessage'))
                    <div class="alert alert-success alert-important alert-dismissible" role="alert">
                        {{ session('successMessage') }}
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                @endif
                @if (session('errorMessage'))
                    <div class="alert alert-danger alert-important alert-dismissible" role="alert">
                        {{ session('errorMessage') }}
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                @endif
                @if (session('infoMessage'))
                    <div class="alert alert-info alert-important alert-dismissible" role="alert">
                        {{ session('infoMessage') }}
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                @endif
                @if (session('warningMessage'))
                    <div class="alert alert-warning alert-important alert-dismissible" role="alert">
                        {{ session('warningMessage') }}
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row text-center align-items-center flex-row-reverse">
                    <div class="col-lg-auto ms-lg-auto">
                        <ul class="list-inline list-inline-dots mb-0">
                            <li class="list-inline-item"><a href="#" target="_blank" class="link-secondary" rel="noopener">Terms and Conditions</a></li>
                            <li class="list-inline-item"><a href="#" class="link-secondary">Privacy Policy</a></li>
                        </ul>
                    </div>
                    <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                        <ul class="list-inline list-inline-dots mb-0">
                            <li class="list-inline-item">
                                Copyright &copy; {{ date('Y') }}
                                <a href="{{ route('home') }}" class="link-secondary">{{ config('app.name') }}</a>.
                                All rights reserved.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>
@stack('footer')
</body>
</html>
