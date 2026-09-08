<div>
    <h1 class="h3 mb-4">Mis metas</h1>

    @if (! $case)
        <div class="alert alert-warning">No tienes un caso activo todavía.</div>
    @elseif ($goals->isEmpty())
        <p class="text-muted">Tu equipo aún no ha definido metas para ti.</p>
    @else
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Contar un avance</h2>
                <form wire:submit="save">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Meta</label>
                            <select class="form-select" wire:model="selectedGoalId">
                                @foreach ($goals->where('status', 'active') as $goal)
                                    <option value="{{ $goal->id }}">{{ $goal->description }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">¿Cómo te fue?</label>
                            <textarea class="form-control" rows="2" wire:model="notes" placeholder="Cuenta cómo te fue con esta actividad..."></textarea>
                        </div>
                        <div class="col-12 form-check">
                            <input type="checkbox" class="form-check-input" id="difficulty" wire:model="had_difficulty">
                            <label class="form-check-label" for="difficulty">Tuve dificultad o no pude hacerlo</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-posuci mt-3">Guardar avance</button>
                </form>
            </div>
        </div>

        @foreach ($goals as $goal)
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge {{ $goal->status === 'active' ? 'bg-success' : ($goal->status === 'paused' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ $goal->statusLabel() }}</span>
                            <span class="text-muted small ms-2">{{ $goal->domain }}</span>
                        </div>
                        @if ($goal->target_date)
                            <span class="text-muted small">Meta al {{ $goal->target_date->format('d/m/Y') }}</span>
                        @endif
                    </div>
                    <p class="mt-2 mb-2 fw-semibold">{{ $goal->description }}</p>

                    @if ($goal->progressReports->isEmpty())
                        <p class="text-muted small mb-0">Todavía no hay avances registrados.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($goal->progressReports as $report)
                                <li class="list-group-item px-0">
                                    <div class="d-flex justify-content-between">
                                        <span class="small text-muted">{{ $report->reported_at->format('d/m/Y H:i') }}</span>
                                        @if ($report->isValidated())
                                            <span class="badge bg-success">Validado por {{ $report->validatedBy?->name }}</span>
                                        @else
                                            <span class="badge bg-secondary">Pendiente de revisión</span>
                                        @endif
                                    </div>
                                    @if ($report->notes)
                                        <p class="mb-0 mt-1">{{ $report->notes }}</p>
                                    @endif
                                    @if ($report->had_difficulty)
                                        <p class="mb-0 text-danger small">Reportó dificultad</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
