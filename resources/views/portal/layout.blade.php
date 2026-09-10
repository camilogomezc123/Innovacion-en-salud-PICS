<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Mi recuperación') · POSUCI 360 Conecta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background-color: #f5f7f8; font-size: 1.05rem; }
        .posuci-navbar { background-color: #0e7490; }
        .posuci-navbar .nav-link, .posuci-navbar .navbar-brand { color: #fff !important; font-weight: 600; }
        .posuci-navbar .nav-link.active { text-decoration: underline; }
        .btn-posuci { background-color: #0e7490; border-color: #0e7490; color: #fff; }
        .btn-posuci:hover { background-color: #0b5d73; border-color: #0b5d73; color: #fff; }
        .card { border-radius: 0.75rem; }
    </style>
</head>
<body>
    @auth('patient')
        @php($actorName = auth('patient')->user()->full_name)
        @php($actorRole = 'Paciente')
    @endauth
    @auth('caregiver')
        @php($actorName = auth('caregiver')->user()->name)
        @php($actorRole = 'Familiar / cuidador')
    @endauth

    <nav class="navbar navbar-expand-lg posuci-navbar mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('portal.home') }}">POSUCI 360 Conecta</a>
            @if(isset($actorName))
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#posuciNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="posuciNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.home') ? 'active' : '' }}" href="{{ route('portal.home') }}">Mi recuperación</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.passport') ? 'active' : '' }}" href="{{ route('portal.passport') }}">Antes y ahora</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.diary') ? 'active' : '' }}" href="{{ route('portal.diary') }}">Mi diario</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.goals') ? 'active' : '' }}" href="{{ route('portal.goals') }}">Mis metas</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.wellbeing') ? 'active' : '' }}" href="{{ route('portal.wellbeing') }}">Cómo me siento</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.support') ? 'active' : '' }}" href="{{ route('portal.support') }}">Necesito ayuda</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.medications') ? 'active' : '' }}" href="{{ route('portal.medications') }}">Medicamentos</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.home-monitoring') ? 'active' : '' }}" href="{{ route('portal.home-monitoring') }}">Monitoreo en casa</a></li>
                        <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.discharge-readiness') ? 'active' : '' }}" href="{{ route('portal.discharge-readiness') }}">Preparación para el alta</a></li>
                        @auth('caregiver')
                            <li class="nav-item"><a class="nav-link {{ request()->routeIs('portal.caregiver-journey') ? 'active' : '' }}" href="{{ route('portal.caregiver-journey') }}">Mi ruta como cuidador</a></li>
                        @endauth
                    </ul>
                    <div class="d-flex align-items-center flex-column flex-lg-row">
                        <span class="text-white me-lg-3 mb-2 mb-lg-0">{{ $actorName }} · {{ $actorRole }}</span>
                        <form method="POST" action="{{ route('portal.logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light">Salir</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </nav>

    <main class="container pb-5">
        @if (session('diary_status'))
            <div class="alert alert-success">{{ session('diary_status') }}</div>
        @endif
        @if (session('goals_status'))
            <div class="alert alert-success">{{ session('goals_status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
