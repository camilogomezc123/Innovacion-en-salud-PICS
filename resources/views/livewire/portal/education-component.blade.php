<div>
    <h1 class="h3 mb-1">Educación</h1>
    <p class="text-muted">Contenido que tu equipo eligió para tu recuperación.</p>

    @if ($assignments->isEmpty())
        <div class="alert alert-warning">Tu equipo aún no te ha asignado contenido educativo.</div>
    @else
        @foreach ($assignments->groupBy('resource.category') as $category => $group)
            <h2 class="h6 text-muted mt-4">{{ \App\Models\EducationResource::CATEGORIES[$category] ?? 'General' }}</h2>
            @foreach ($group as $assignment)
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h3 class="h5 mb-2">{{ $assignment->resource->title }}</h3>
                            @if ($assignment->isViewed())
                                <span class="badge bg-success text-nowrap">Leído</span>
                            @endif
                        </div>
                        <p class="mb-2" style="white-space: pre-line;">{{ $assignment->resource->body }}</p>
                        @if ($assignment->notes)
                            <p class="text-muted small mb-2"><em>{{ $assignment->notes }}</em></p>
                        @endif
                        @if (! $assignment->isViewed())
                            <button type="button" wire:click="markViewed({{ $assignment->id }})" class="btn btn-sm btn-posuci">Marcar como leído</button>
                        @endif
                    </div>
                </div>
            @endforeach
        @endforeach
    @endif
</div>
