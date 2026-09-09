<div>
    <h1 class="h3 mb-1">Preparación para el alta</h1>
    <p class="text-muted">Revisa cada tema con calma. Marca los que ya leíste — tu equipo verificará contigo que quedaron claros.</p>

    @if (! $check || $check->items->isEmpty())
        <div class="alert alert-warning">Tu equipo aún no ha preparado tu plan de alta.</div>
    @else
        <div class="card shadow-sm">
            <ul class="list-group list-group-flush">
                @foreach ($check->items as $item)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="h6 mb-1">{{ $item->topicLabel() }}</h2>
                                @if ($item->staff_instructions)
                                    <p class="mb-0">{{ $item->staff_instructions }}</p>
                                @endif
                            </div>
                            <div class="text-end text-nowrap">
                                @if ($item->understood === true)
                                    <span class="badge bg-success">Comprensión verificada</span>
                                @elseif ($item->reviewed_at)
                                    <span class="badge bg-info text-dark">Ya lo revisaste</span>
                                @else
                                    <span class="badge bg-light text-dark border">Sin revisar</span>
                                @endif
                            </div>
                        </div>

                        @if (! $item->reviewed_at)
                            <button type="button" wire:click="reviewItem({{ $item->id }})" class="btn btn-sm btn-posuci mt-2">Ya lo revisé</button>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
