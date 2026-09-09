<div>
    <h1 class="h3 mb-1">Necesito ayuda</h1>
    <p class="text-muted">Cuéntanos qué dificultad tienes. Tu equipo la revisará y te responderá aquí mismo.</p>

    @if (session('support_status'))
        <div class="alert alert-success">{{ session('support_status') }}</div>
    @endif

    <div class="alert alert-secondary small">
        Esto no es un servicio de vigilancia permanente. Si tienes una emergencia médica, comunícate con los canales de urgencia de tu institución.
    </div>

    @if (! $case)
        <div class="alert alert-warning">No tienes un caso activo todavía.</div>
    @else
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Reportar una dificultad</h2>
                <form wire:submit="save">
                    <div class="mb-3">
                        <label class="form-label">¿Qué dificultad tienes?</label>
                        <textarea class="form-control" rows="3" wire:model="description"></textarea>
                        @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">¿Qué tan urgente sientes que es?</label>
                        <select class="form-select" wire:model="priority">
                            <option value="baja">Puede esperar</option>
                            <option value="media">Me gustaría que la revisen pronto</option>
                            <option value="alta">Es urgente para mí</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-posuci">Enviar</button>
                </form>
            </div>
        </div>

        <h2 class="h5 mb-3">Mis solicitudes</h2>
        @forelse ($requests as $request)
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <span class="badge {{ match($request->status) { 'resuelta' => 'bg-success', 'respondida' => 'bg-primary', 'escalada' => 'bg-danger', default => 'bg-secondary' } }}">{{ $request->statusLabel() }}</span>
                        <span class="text-muted small">{{ $request->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <p class="mt-2 mb-1">{{ $request->description }}</p>
                    @if ($request->isAnswered())
                        <div class="alert alert-light border mt-2 mb-0">
                            <strong>Respuesta de tu equipo:</strong>
                            <p class="mb-0">{{ $request->response_text }}</p>
                        </div>
                    @else
                        <p class="text-muted small mb-0">Aún sin respuesta.</p>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted">Todavía no has reportado ninguna dificultad.</p>
        @endforelse
    @endif
</div>
