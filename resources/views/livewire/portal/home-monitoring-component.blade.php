<div>
    <h1 class="h3 mb-1">Monitoreo en casa</h1>
    <p class="text-muted">Registra aquí las lecturas que tomes en casa (oxímetro, tensiómetro, termómetro, etc.). Es un registro manual — no se conecta automáticamente a ningún dispositivo todavía.</p>

    @if (session('monitoring_status'))
        <div class="alert alert-success">{{ session('monitoring_status') }}</div>
    @endif

    @if (! $case)
        <div class="alert alert-warning">No tienes un caso activo todavía.</div>
    @else
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Registrar una lectura</h2>
                <form wire:submit="save">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" wire:model="reading_type">
                                @foreach (\App\Models\HomeMonitoringReading::READING_TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Valor</label>
                            <input type="text" class="form-control" wire:model="value" placeholder="Ej: 97 o 120/80">
                            @error('value') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unidad (opcional)</label>
                            <input type="text" class="form-control" wire:model="unit" placeholder="Ej: %, lpm, mmHg">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha y hora</label>
                            <input type="datetime-local" class="form-control" wire:model="measured_at">
                            @error('measured_at') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notas (opcional)</label>
                            <input type="text" class="form-control" wire:model="notes">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-posuci mt-3">Guardar lectura</button>
                </form>
            </div>
        </div>

        <h2 class="h5 mb-3">Historial</h2>
        @if ($readings->isEmpty())
            <p class="text-muted">Todavía no hay lecturas registradas.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm bg-white shadow-sm">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Registrado por</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($readings as $reading)
                            <tr>
                                <td>{{ $reading->measured_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $reading->typeLabel() }}</td>
                                <td>{{ $reading->value }} {{ $reading->unit }}</td>
                                <td>{{ $reading->recordedBy?->name ?? $reading->recordedBy?->full_name ?? '—' }}</td>
                                <td>{{ $reading->notes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
