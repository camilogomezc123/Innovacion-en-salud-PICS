@extends('portal.layout')

@section('title', 'Mi recuperación')

@section('content')

@if (! $case)
    <div class="alert alert-warning">
        Todavía no tienes un programa de seguimiento activo. Cuando tu equipo te inscriba, aparecerá aquí.
    </div>
@else
    @php($g = $gamification)

    <div class="game-hero mb-4 game-pop">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <span class="game-mascot">🦸</span>
            </div>
            <div class="col">
                <h1 class="h3 mb-1">¡Hola{{ $actorFirstName ? ', '.$actorFirstName : '' }}! 👋</h1>
                <span class="game-speech">Cada cosita que registras hoy suma para tu recuperación. ¡Vamos por más! 💪</span>
                <div class="mt-2">
                    <span class="badge bg-white bg-opacity-75 text-dark">Caso {{ $case->case_number }}</span>
                    <span class="badge bg-white bg-opacity-75 text-dark">Etapa: {{ $case->clinicalStageLabel() }}</span>
                </div>
            </div>
        </div>
    </div>

    @if ($g)
        <div class="xp-card mb-4 game-pop">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="xp-level-badge">{{ $g['level'] }}</div>
                <div class="flex-grow-1" style="min-width: 220px;">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $g['levelTitle'] }}</strong>
                        <span class="text-muted small">{{ $g['xpIntoLevel'] }} / {{ $g['xpForNextLevel'] }} XP</span>
                    </div>
                    <div class="xp-bar-track mt-1">
                        <div class="xp-bar-fill" style="width: {{ $g['xpProgressPct'] }}%;"></div>
                    </div>
                </div>
                <div class="text-center">
                    <div class="fs-4 fw-black" style="font-weight:900;">{{ $g['points'] }}</div>
                    <div class="text-muted small">puntos totales</div>
                </div>
                @if ($g['streakDays'] > 0)
                    <span class="streak-chip"><span class="streak-flame">🔥</span> {{ $g['streakDays'] }} día{{ $g['streakDays'] === 1 ? '' : 's' }} seguido{{ $g['streakDays'] === 1 ? '' : 's' }}</span>
                @endif
            </div>

            <div class="badge-shelf mt-3">
                @foreach ($g['badges'] as $badge)
                    <span class="badge-chip {{ $badge['unlocked'] ? '' : 'locked' }}" title="{{ $badge['unlocked'] ? '¡Insignia desbloqueada!' : 'Insignia bloqueada — sigue participando' }}">
                        <span class="badge-icon">{{ $badge['icon'] }}</span> {{ $badge['label'] }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @php($pendingCount = $unreadEducation + $pendingReadinessItems + $openSupportRequests + ($pendingJourneySteps ?? 0))
    @if ($pendingCount > 0)
        <div class="alert alert-info mb-4">
            <strong>🔔 Tienes {{ $pendingCount }} pendiente{{ $pendingCount === 1 ? '' : 's' }}:</strong>
            <ul class="mb-0 mt-1">
                @if ($unreadEducation > 0)
                    <li><a href="{{ route('portal.education') }}">{{ $unreadEducation }} contenido{{ $unreadEducation === 1 ? '' : 's' }} educativo{{ $unreadEducation === 1 ? '' : 's' }} sin leer</a></li>
                @endif
                @if ($pendingReadinessItems > 0)
                    <li><a href="{{ route('portal.discharge-readiness') }}">{{ $pendingReadinessItems }} tema{{ $pendingReadinessItems === 1 ? '' : 's' }} de preparación para el alta sin revisar</a></li>
                @endif
                @if ($openSupportRequests > 0)
                    <li><a href="{{ route('portal.support') }}">{{ $openSupportRequests }} solicitud{{ $openSupportRequests === 1 ? '' : 'es' }} esperando respuesta</a></li>
                @endif
                @if (($pendingJourneySteps ?? 0) > 0)
                    <li><a href="{{ route('portal.caregiver-journey') }}">{{ $pendingJourneySteps }} paso{{ $pendingJourneySteps === 1 ? '' : 's' }} de tu ruta como cuidador sin completar</a></li>
                @endif
            </ul>
        </div>
    @endif

    <h2 class="h5 mb-3">🗺️ Tus misiones</h2>
    <div class="mission-grid">
        @foreach ($missions as $mission)
            <a href="{{ $mission['url'] }}" class="mission-card game-pop">
                @if (($mission['pending'] ?? 0) > 0)
                    <span class="mission-pending-dot">{{ $mission['pending'] }}</span>
                @endif
                <span class="mission-icon" style="background: {{ $mission['color'] }};">{{ $mission['icon'] }}</span>
                <div class="mission-title">{{ $mission['title'] }}</div>
                <div class="mission-desc">{{ $mission['description'] }}</div>
            </a>
        @endforeach
    </div>
@endif
@endsection
