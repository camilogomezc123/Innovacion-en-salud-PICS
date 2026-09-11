<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#7c3aed">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <title>@yield('title', 'Mi recuperación') · POSUCI 360 Conecta</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/portal-game.css') }}">
    <style>
        body { background-color: #f5f7f8; font-size: 1.05rem; }
        .posuci-navbar { background: linear-gradient(135deg, #7c3aed, #0e7490); border-radius: 0 0 1.5rem 1.5rem; }
        .posuci-navbar .nav-link, .posuci-navbar .navbar-brand { color: #fff !important; font-weight: 700; }
        .posuci-navbar .nav-link { border-radius: 999px; padding-left: .85rem !important; padding-right: .85rem !important; }
        .posuci-navbar .nav-link.active { background: rgba(255,255,255,.22); }
        .btn-posuci { background-color: #0e7490; border-color: #0e7490; color: #fff; }
        .btn-posuci:hover { background-color: #0b5d73; border-color: #0b5d73; color: #fff; }
        .card { border-radius: 0.75rem; }
    </style>
</head>
@auth('patient')
    @php($actorName = auth('patient')->user()->full_name)
    @php($actorRole = 'Paciente')
    @php($actorEasyMode = auth('patient')->user()->portal_easy_mode)
@endauth
@auth('caregiver')
    @php($actorName = auth('caregiver')->user()->name)
    @php($actorRole = 'Familiar / cuidador')
    @php($actorEasyMode = auth('caregiver')->user()->portal_easy_mode)
    @php($caregiverCase = \App\Http\Controllers\Portal\PortalHomeController::currentCase())
    @php($canAccessCaregiverJourney = $caregiverCase && \App\Support\Posuci\CaseAccess::caregiverCanAccessJourney(auth('caregiver')->user(), $caregiverCase))
@endauth
@php($navModules = collect([
    ['icon' => '🏠', 'label' => 'Mi recuperación', 'route' => 'portal.home', 'color' => 'linear-gradient(135deg,#7c3aed,#ec4899)'],
    ['icon' => '📅', 'label' => 'Mi calendario', 'route' => 'portal.calendar', 'color' => 'linear-gradient(135deg,#0ea5e9,#7c3aed)'],
    ['icon' => '📖', 'label' => 'Antes y ahora', 'route' => 'portal.passport', 'color' => 'linear-gradient(135deg,#0ea5e9,#0e7490)'],
    ['icon' => '✍️', 'label' => 'Mi diario', 'route' => 'portal.diary', 'color' => 'linear-gradient(135deg,#7c3aed,#5b21b6)'],
    ['icon' => '🎯', 'label' => 'Mis metas', 'route' => 'portal.goals', 'color' => 'linear-gradient(135deg,#f97316,#ea580c)'],
    ['icon' => '💙', 'label' => 'Cómo me siento', 'route' => 'portal.wellbeing', 'color' => 'linear-gradient(135deg,#ec4899,#db2777)'],
    ['icon' => '🆘', 'label' => 'Necesito ayuda', 'route' => 'portal.support', 'color' => 'linear-gradient(135deg,#ef4444,#b91c1c)'],
    ['icon' => '💊', 'label' => 'Medicamentos', 'route' => 'portal.medications', 'color' => 'linear-gradient(135deg,#0ea5e9,#2563eb)'],
    ['icon' => '🩺', 'label' => 'Monitoreo en casa', 'route' => 'portal.home-monitoring', 'color' => 'linear-gradient(135deg,#22c55e,#15803d)'],
    ['icon' => '📈', 'label' => 'Mi progreso', 'route' => 'portal.progress', 'color' => 'linear-gradient(135deg,#7c3aed,#0ea5e9)'],
    ['icon' => '📚', 'label' => 'Educación', 'route' => 'portal.education', 'color' => 'linear-gradient(135deg,#6366f1,#4338ca)'],
    ['icon' => '🎓', 'label' => 'Preparación para el alta', 'route' => 'portal.discharge-readiness', 'color' => 'linear-gradient(135deg,#facc15,#ca8a04)'],
    ['icon' => '🖨️', 'label' => 'Resumen para tu cita', 'route' => 'portal.summary', 'color' => 'linear-gradient(135deg,#14b8a6,#0e7490)'],
]))
@if ($canAccessCaregiverJourney ?? false)
    @php($navModules->push(['icon' => '🤝', 'label' => 'Mi ruta como cuidador', 'route' => 'portal.caregiver-journey', 'color' => 'linear-gradient(135deg,#14b8a6,#0f766e)']))
@endif
<body class="game-mode {{ ($actorEasyMode ?? false) ? 'easy-mode' : '' }}">
    <nav class="navbar navbar-expand-lg posuci-navbar mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('portal.home') }}">POSUCI 360 Conecta</a>
            @if(isset($actorName))
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#posuciNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="posuciNav">
                    <ul class="navbar-nav me-auto">
                        @foreach ($navModules as $module)
                            <li class="nav-item"><a class="nav-link {{ request()->routeIs($module['route']) ? 'active' : '' }}" href="{{ route($module['route']) }}">{{ $module['icon'] }} {{ $module['label'] }}</a></li>
                        @endforeach
                    </ul>
                    <div class="d-flex align-items-center flex-column flex-lg-row">
                        <span class="text-white me-lg-3 mb-2 mb-lg-0">{{ $actorName }} · {{ $actorRole }}</span>
                        @livewire('portal.notification-center-component')
                        <div class="dropdown me-lg-2 mb-2 mb-lg-0">
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                ⚙️ Ajustes
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 260px;">
                                <form method="POST" action="{{ route('portal.easy-mode.toggle') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item rounded-3 mb-1">
                                        {{ $actorEasyMode ? '🔎 Modo fácil: activado ✓' : '🔎 Activar modo fácil' }}
                                    </button>
                                </form>
                                <button type="button" id="pushToggleBtn" class="dropdown-item rounded-3 mb-1" style="display:none;"
                                    data-key-url="{{ route('portal.push.public-key') }}"
                                    data-subscribe-url="{{ route('portal.push.subscribe') }}"
                                    data-unsubscribe-url="{{ route('portal.push.unsubscribe') }}"
                                    data-csrf="{{ csrf_token() }}">
                                    🔔 Activar notificaciones
                                </button>
                                <button type="button" id="installAppBtn" class="dropdown-item rounded-3 mb-1" style="display:none;">
                                    📲 Instalar app
                                </button>
                                <hr class="dropdown-divider my-1">
                                <form method="POST" action="{{ route('portal.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item rounded-3 text-danger">🚪 Salir</button>
                                </form>
                            </div>
                        </div>
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

    @if (isset($actorName))
        <button type="button" id="readAloudBtn" class="btn btn-game" style="position:fixed; bottom:1.25rem; right:1.25rem; z-index:1040; border-radius:999px; box-shadow:0 10px 24px -10px rgba(15,23,42,.5); display:none;">
            🔊 Leer esta página
        </button>

        <button type="button" class="btn btn-game-outline bg-white" data-bs-toggle="offcanvas" data-bs-target="#navModulesOffcanvas"
            style="position:fixed; bottom:1.25rem; left:1.25rem; z-index:1040; border-radius:999px; box-shadow:0 10px 24px -10px rgba(15,23,42,.5);">
            🗺️ Módulos
        </button>

        <div class="offcanvas offcanvas-bottom" tabindex="-1" id="navModulesOffcanvas" aria-labelledby="navModulesOffcanvasLabel" style="max-height: 82vh; border-radius: 1.5rem 1.5rem 0 0;">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="navModulesOffcanvasLabel">🗺️ Todos los módulos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
            </div>
            <div class="offcanvas-body">
                <div class="mission-grid">
                    @foreach ($navModules as $module)
                        <a href="{{ route($module['route']) }}" class="mission-card game-pop">
                            <span class="mission-icon" style="background: {{ $module['color'] }};">{{ $module['icon'] }}</span>
                            <div class="mission-title">{{ $module['label'] }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="{{ asset('js/portal-game.js') }}"></script>
    <script src="{{ asset('js/portal-push.js') }}"></script>
    <script src="{{ asset('js/portal-install.js') }}"></script>
    <script src="{{ asset('js/portal-voice.js') }}"></script>
</body>
</html>
