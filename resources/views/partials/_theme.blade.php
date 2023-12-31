@php
    $currentTheme = App\Models\Theme::whereActive(true)->first();
@endphp
@if($currentTheme)
    <style>
        /* Theme Specific */
        a {
            color: {{ $currentTheme->primary }};
        }

        .btn-link, .btn-link:hover {
            color: {{ $currentTheme->primary }};
        }

        .bg-primary {
            background-color: {{ $currentTheme->primary }} !important;
        }

        .btn-check:checked + .btn, :not(.btn-check) + .btn:active, .btn:first-child:active, .btn.active, .btn.show {
            color: {{ $currentTheme->primary }};
            border-color: {{ $currentTheme->primary }};
            background-color: {{ $currentTheme->tertiary }};
        }

        .breadcrumb a {
            color: {{ $currentTheme->primary }};
        }

        .navbar[data-bs-theme=dark] {
            background-color: {{ $currentTheme->nav_background }};
        }

        .navbar-expand-lg .nav-item.active::after {
            border-color: {{ $currentTheme->primary }};
        }

        .btn-primary {
            background-color: {{ $currentTheme->primary }};
        }

        .btn-primary:hover {
            background-color: {{ $currentTheme->secondary }};
        }

        .btn-primary:active {
            background-color: {{ $currentTheme->secondary }} !important;
            color: #ffffff !important;
        }

        .btn-outline-primary {
            border-color: {{ $currentTheme->primary }};
            color: {{ $currentTheme->primary }};
        }

        .btn-outline-primary:hover {
            background-color: {{ $currentTheme->primary }};
        }

        .seating-plan .seat.available {
            background-color: {{ $currentTheme->seat_available }};
        }

        .seating-plan .seat.disabled {
            background-color: {{ $currentTheme->seat_disabled }};
        }

        .seating-plan .seat.taken {
            background-color: {{ $currentTheme->seat_taken }};
        }

        .seating-plan .seat.seat-clan {
            background-color: {{ $currentTheme->seat_clan }};
        }

        .seating-plan .seat.seat-mine {
            background-color: {{ $currentTheme->seat_selected }};
        }

        /* Theme CSS Override */
        {{ $currentTheme->css }}
    </style>
@endif
