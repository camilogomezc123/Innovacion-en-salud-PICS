<div>
    <h1 class="h3 mb-1">📅 Mi calendario</h1>
    <p class="text-muted mb-4">Citas, terapias, medicamentos con horario y tus propios recordatorios, todo en un solo lugar.</p>

    @if (session('calendar_status'))
        <div class="alert alert-success">{{ session('calendar_status') }}</div>
    @endif

    @if (! $case)
        <div class="alert alert-warning">No tienes un caso activo todavía.</div>
    @else
        <div class="feed-card mb-3">
            <div class="d-flex flex-wrap gap-3 small">
                <span><span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#0ea5e9;"></span> Cita</span>
                <span><span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#7c3aed;"></span> Terapia</span>
                <span><span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#64748b;"></span> Tarea</span>
                <span><span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#0e7490;"></span> Remisión</span>
                <span><span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#f97316;"></span> Medicamento</span>
                <span><span style="display:inline-block;width:.8rem;height:.8rem;border-radius:50%;background:#ec4899;"></span> Mi recordatorio</span>
            </div>
            <p class="text-muted small mb-0 mt-2">💡 Haz clic en una cita o terapia para confirmar tu asistencia o avisar que no podrás ir.</p>
        </div>

        <div class="feed-card mb-4" id="portalCalendar" wire:ignore></div>

        <div class="feed-card">
            <h2 class="h5 mb-3">➕ Agregar recordatorio personal</h2>
            <p class="text-muted small">Solo tú lo ves — no es una cita clínica, es tu propio espacio de organización.</p>
            <form wire:submit="addReminder">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">¿Qué quieres recordar?</label>
                        <input type="text" class="form-control" wire:model="title" placeholder="Ej: Tomar agua, llamar a mi hermana">
                        @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha y hora</label>
                        <input type="datetime-local" class="form-control" wire:model="remind_at">
                        @error('remind_at') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nota (opcional)</label>
                        <input type="text" class="form-control" wire:model="notes">
                    </div>
                </div>
                <button type="submit" class="btn btn-game mt-3">📌 Agregar</button>
            </form>
        </div>

        <div class="modal fade" id="appointmentResponseModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 1.5rem;">
                    <div class="modal-header">
                        <h5 class="modal-title">📅 <span id="appointmentResponseTitle"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">¿Podrás asistir?</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-danger" id="appointmentResponseDecline">❌ No podré asistir</button>
                        <button type="button" class="btn btn-game" id="appointmentResponseConfirm">✅ Confirmaré</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('portalCalendar');
                if (! el || typeof FullCalendar === 'undefined') {
                    return;
                }

                var calendar = new FullCalendar.Calendar(el, {
                    locale: 'es',
                    initialView: 'dayGridMonth',
                    height: 'auto',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
                    events: @js(route('portal.calendar.events')),
                    eventClick: function (info) {
                        var props = info.event.extendedProps;
                        if (! props.respondable) {
                            return;
                        }

                        var modalEl = document.getElementById('appointmentResponseModal');
                        document.getElementById('appointmentResponseTitle').textContent = info.event.title;

                        var wireRoot = el.closest('[wire\\:id]');
                        var componentId = wireRoot ? wireRoot.getAttribute('wire:id') : null;
                        var component = componentId && typeof Livewire !== 'undefined' ? Livewire.find(componentId) : null;

                        var modal = window.bootstrap ? new bootstrap.Modal(modalEl) : null;

                        document.getElementById('appointmentResponseConfirm').onclick = function () {
                            if (component) { component.call('respondToAppointment', props.id, 'confirmada'); }
                            if (modal) { modal.hide(); }
                        };
                        document.getElementById('appointmentResponseDecline').onclick = function () {
                            if (component) { component.call('respondToAppointment', props.id, 'no_asistira'); }
                            if (modal) { modal.hide(); }
                        };

                        if (modal) { modal.show(); }
                    },
                });
                calendar.render();

                if (typeof Livewire !== 'undefined') {
                    Livewire.on('calendar-refresh', function () {
                        calendar.refetchEvents();
                    });
                }
            });
        </script>
    @endif
</div>
