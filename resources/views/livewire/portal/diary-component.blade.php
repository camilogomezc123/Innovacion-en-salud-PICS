<div>
    <h1 class="h3 mb-4">Mi diario</h1>

    @if (! $case)
        <div class="alert alert-warning">No tienes un caso activo todavía.</div>
    @else
        @if ($canWrite)
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Escribir una entrada</h2>
                    <form wire:submit="save">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Fecha</label>
                                <input type="date" class="form-control" wire:model="entry_date">
                                @error('entry_date') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">¿Cómo estuvo el día?</label>
                                <textarea class="form-control" rows="3" wire:model="content" placeholder="Cuéntale a la familia cómo fue el día..."></textarea>
                                @error('content') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mensaje para el paciente (opcional)</label>
                                <textarea class="form-control" rows="2" wire:model="message_to_patient"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Un recuerdo significativo (opcional)</label>
                                <textarea class="form-control" rows="2" wire:model="meaningful_memory"></textarea>
                            </div>
                            <div class="col-12 form-check">
                                <input type="checkbox" class="form-check-input" id="visible" wire:model="visible_to_patient">
                                <label class="form-check-label" for="visible">El paciente puede leer esta entrada</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-posuci mt-3">Guardar entrada</button>
                    </form>
                </div>
            </div>
        @endif

        <h2 class="h5 mb-3">Entradas</h2>

        @forelse ($entries as $entry)
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <strong>{{ $entry->entry_date->format('d/m/Y') }}</strong>
                        <span class="text-muted small">{{ $entry->authorable?->name ?? 'Familia' }}</span>
                    </div>
                    <p class="mb-2 mt-2">{{ $entry->content }}</p>
                    @if ($entry->message_to_patient)
                        <p class="mb-1"><em>Mensaje para ti:</em> {{ $entry->message_to_patient }}</p>
                    @endif
                    @if ($entry->meaningful_memory)
                        <p class="mb-0"><em>Recuerdo:</em> {{ $entry->meaningful_memory }}</p>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted">{{ $isPatient ? 'Aún no hay entradas visibles para ti.' : 'Todavía no hay entradas registradas.' }}</p>
        @endforelse
    @endif
</div>
