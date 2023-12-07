<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
        <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>
    <body class="d-flex flex-column bg-white">
        <div class="row g-0 flex-fill">
            <div class="col-12 col-lg-6 col-xl-4 border-top-wide border-primary d-flex flex-column justify-content-center">
                <div class="container container-tight my-5 px-lg-5 text-center">
                    <h1 class="mb-4">
                        {{ config('app.name') }}
                    </h1>
                    <p>
                        Login to manage your event tickets and seats.
                    </p>
                    <p>
                        <a href="{{ route('login.discord.redirect') }}" class="btn btn-discord">
                            <i class="icon ti ti-brand-discord-filled"></i>
                            Login with Discord
                        </a>
                    </p>
                </div>
            </div>
            <div class="col-12 col-lg-6 col-xl-8 d-none d-lg-block">
                <div class="bg-cover h-100 min-vh-100" style="background-image: url('{{ Vite::asset('resources/img/cover.jpg') }}')"></div>
            </div>
        </div>
    </body>
</html>
