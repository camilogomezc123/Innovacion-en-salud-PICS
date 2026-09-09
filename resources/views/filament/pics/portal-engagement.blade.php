<x-filament-panels::page>
    @php($s = $this->summary)
    <div class="mx-auto w-full max-w-6xl space-y-5">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            Mide el uso real de <strong>{{ url('/portal/login') }}</strong> por parte de los pacientes y sus cuidadores — no reemplaza el juicio clínico, es un indicador de participación y respuesta.
        </div>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['Casos con cuidador autorizado', $s['caregiver_authorized_pct']],
                ['Casos con al menos un ingreso al portal', $s['any_login_pct']],
                ['Casos con actividad en el diario', $s['diary_activity_pct']],
                ['Casos con autorreporte de bienestar', $s['wellbeing_self_report_pct']],
            ] as [$label, $value])
                <article class="rounded-xl border bg-white p-4">
                    <p class="text-xs font-bold text-slate-600">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-black text-slate-950">{{ $value === null ? 'Sin dato' : number_format($value, 1, ',', '.').'%' }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <article class="rounded-xl border bg-white p-4">
                <p class="text-xs font-bold text-slate-600">Reportes de meta por caso (promedio)</p>
                <p class="mt-1 text-2xl font-black text-slate-950">{{ $s['avg_goal_reports_per_case'] ?? 'Sin dato' }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-xs font-bold text-slate-600">De esos reportes, hechos por el paciente</p>
                <p class="mt-1 text-2xl font-black text-slate-950">{{ $s['patient_report_share_pct'] === null ? 'Sin dato' : number_format($s['patient_report_share_pct'], 1, ',', '.').'%' }}</p>
            </article>
            <article class="rounded-xl border bg-white p-4">
                <p class="text-xs font-bold text-slate-600">Tiempo promedio de respuesta a solicitudes</p>
                <p class="mt-1 text-2xl font-black text-slate-950">{{ $s['avg_support_response_hours'] === null ? 'Sin dato' : number_format($s['avg_support_response_hours'], 1, ',', '.').' h' }}</p>
            </article>
        </section>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
