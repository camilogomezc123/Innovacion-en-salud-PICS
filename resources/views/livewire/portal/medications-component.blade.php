<div>
    <h1 class="h3 mb-1">Medicamentos</h1>
    <p class="text-muted">La lista de medicamentos que continúas, los nuevos y los que se suspendieron, según lo conciliado por tu equipo.</p>

    @if (! $reconciliation || $reconciliation->items->isEmpty())
        <div class="alert alert-warning">Tu equipo aún no ha registrado la conciliación de medicamentos.</div>
    @else
        <div class="card shadow-sm">
            <ul class="list-group list-group-flush">
                @foreach ($reconciliation->items as $item)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="h6 mb-1">{{ $item->medication_name }}</h2>
                                <p class="text-muted small mb-1">
                                    {{ $item->dose ?: 'Dosis sin especificar' }}
                                    @if ($item->route)
                                        · {{ \App\Models\MedicationReconciliationItem::ROUTES[$item->route] ?? $item->route }}
                                    @endif
                                    @if ($item->frequency)
                                        · {{ $item->frequency }}
                                    @endif
                                </p>
                                @if ($item->patient_instructions)
                                    <p class="mb-0">{{ $item->patient_instructions }}</p>
                                @endif
                            </div>
                            <span @class([
                                'badge text-nowrap',
                                'bg-success' => $item->status === 'continua',
                                'bg-info text-dark' => $item->status === 'nueva',
                                'bg-warning text-dark' => $item->status === 'ajustada',
                                'bg-danger' => $item->status === 'suspendida',
                            ])>{{ $item->statusLabel() }}</span>
                        </div>
                        <a href="{{ route('portal.support', ['tipo' => 'duda_medicamento', 'medicamento' => $item->medication_name]) }}" class="small">Tengo una duda sobre este medicamento</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
