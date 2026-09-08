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
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Caso</h2>
                    <p class="fs-5 fw-bold mb-0">{{ $case->case_number }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Metas activas</h2>
                    <p class="fs-5 fw-bold mb-0">{{ $pendingGoals }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted mb-1">Ingreso al programa</h2>
                    <p class="fs-5 fw-bold mb-0">{{ $case->enrollment_at?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <a href="{{ route('portal.diary') }}" class="card shadow-sm h-100 text-decoration-none">
                <div class="card-body">
                    <h2 class="h5">Mi diario</h2>
                    <p class="text-muted mb-0">Lee lo que tu familia ha escrito durante tu recuperación.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('portal.goals') }}" class="card shadow-sm h-100 text-decoration-none">
                <div class="card-body">
                    <h2 class="h5">Mis metas</h2>
                    <p class="text-muted mb-0">Consulta tus metas de recuperación y cuenta cómo te ha ido.</p>
                </div>
            </a>
        </div>
    </div>
@endif
@endsection
