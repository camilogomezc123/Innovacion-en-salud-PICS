@extends('portal.layout')

@section('title', 'Mi recuperación')

@section('content')
<h1 class="h3 mb-4">Mi recuperación</h1>

@if (! $case)
    <div class="alert alert-warning">
        Todavía no tienes un programa de seguimiento activo. Cuando tu equipo te inscriba, aparecerá aquí.
    </div>
@else
    <div class="row g-3">
        <div class="col-md-3 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Caso</h2>
                    <p class="fs-5 fw-bold mb-0">{{ $case->case_number }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Etapa actual</h2>
                    <p class="fs-5 fw-bold mb-0">{{ $case->clinicalStageLabel() }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Metas activas</h2>
                    <p class="fs-5 fw-bold mb-0">{{ $pendingGoals }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Ingreso al programa</h2>
                    <p class="fs-5 fw-bold mb-0">{{ $case->enrollment_at?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                </div>
            </div>
        </div>
    </div>

    @php($pendingCount = $unreadEducation + $pendingReadinessItems + $openSupportRequests + ($pendingJourneySteps ?? 0))
    @if ($pendingCount > 0)
        <div class="alert alert-info mt-3 mb-0">
            <strong>Tienes {{ $pendingCount }} pendiente{{ $pendingCount === 1 ? '' : 's' }}:</strong>
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

    <h2 class="h5 mt-4 mb-3">Accesos</h2>
    <div class="row g-3">
        @foreach ($shortcuts as $shortcut)
            <div class="col-md-4">
                <a href="{{ $shortcut['url'] }}" class="card shadow-sm h-100 text-decoration-none">
                    <div class="card-body">
                        <h3 class="h6 mb-1">{{ $shortcut['title'] }}</h3>
                        <p class="text-muted small mb-0">{{ $shortcut['description'] }}</p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif
@endsection
