# POSUCI 360 Conecta — Estado del proyecto

Última actualización: 2026-09-09 (Etapa 1 cerrada: pasaporte de recuperación + bandeja de solicitudes/dificultades, sobre la Iteración 1 + módulo PICS clínico ya existentes).

## Cómo ejecutar la demo

```powershell
.\serve.ps1
```

Requiere PHP 8.4 portátil en `runtime\php\` (ya incluido en este equipo; no se sube a git) y la base de datos sqlite local en `database\database.sqlite`.

Para reiniciar la demo sin afectar otros datos:

```powershell
php artisan migrate:fresh
php artisan db:seed                          # admin + catálogo institucional
php artisan db:seed --class=PicsDemoSeeder    # caso PICS de ejemplo
php artisan db:seed --class=PosuciDemoSeeder  # paciente, cuidador, diario y metas de ejemplo
```

### Accesos de prueba (solo datos ficticios)

| Rol | URL | Usuario | Contraseña |
|---|---|---|---|
| Profesional (panel `/pics`) | `/pics/login` | `admin` | `Pics2026!` |
| Paciente (portal) | `/portal/login` | `paciente.demo@posuci360.test` | `Posuci2026!` |
| Cuidador (portal) | `/portal/login` | `familia.demo@posuci360.test` | `Posuci2026!` |

## Qué incluye la Iteración 1 (implementado y verificado)

- **Autenticación separada**: dos guards de sesión nuevos e independientes del staff (`patient`, `caregiver`), sin exponer ningún dato clínico a través del panel administrativo.
- **Diario de recuperación**: el cuidador autorizado escribe entradas de texto; el paciente las lee solo si están marcadas como visibles. El profesional las ve (solo lectura) desde el caso en `/pics`.
- **Metas de recuperación**: el profesional las crea desde `/pics → Metas de recuperación`; el paciente o el cuidador reportan avances desde el portal; el profesional valida cada reporte por separado (`/pics → Seguimiento POSUCI` o desde la propia meta). El reporte y la validación quedan siempre separados en la base de datos.
- **Autorización familiar revocable**: un cuidador solo entra al caso que un miembro del staff le autorizó explícitamente; revocar la autorización le quita el acceso de inmediato.
- **Aislamiento por caso**: cada actor solo ve su propio caso, verificado con peticiones HTTP directas (no solo ocultando botones).

Verificado con 8 pruebas automatizadas nuevas (`tests/Feature/Posuci/PosuciIteration1Test.php`) más las 124 pruebas existentes del proyecto (todas pasan). También se probó manualmente el flujo completo en navegador vía `curl` (login, diario, metas) contra el servidor local.

## Módulo clínico PICS (portado desde "Panel de control", 2026-09-09)

El seguimiento PICS (`/pics → Casos → Seguimientos`) ya no usa campos de texto libre: usa los mismos instrumentos validados que el usuario tenía construidos en `C:\xampp\htdocs\Panel de control` — Pfeiffer/AMT (cognición), MoCA (solo si Pfeiffer ≥ 3 errores), HADS-A (ansiedad), PHQ-9 (depresión), PC-PTSD-5 (estrés postraumático), PTG-SF (crecimiento postraumático, solo checkpoints 3m/6m/12m) y PICS-F (sobrecarga del cuidador). Los puntajes y las banderas de "tamizaje positivo" se calculan siempre desde las respuestas — nunca se diligencian a mano.

`PicsCase` ahora calcula un **riesgo PICS de 7 factores** (estancia UCI, ventilación mecánica, delirium, edad, Barthel, choque/sepsis, debilidad adquirida en UCI por MRC/handgrip) — mismo algoritmo y mismos puntos de corte que el original. Se dispara con la acción "Recalcular riesgo" en el caso (no es automático, porque estos datos se completan por etapas).

El portal ganó una pantalla nueva, **"Cómo me siento"** (`/portal/bienestar`): el paciente autoadministra PHQ-9, HADS-A, PC-PTSD-5 y PTG-SF; el cuidador autoadministra PICS-F. Pfeiffer/AMT, MoCA y el tamizaje de disfagia **no** están en el portal — por seguridad clínica, esos requieren un evaluador presencial calificando respuestas correctas/incorrectas. Lo que el paciente/familia envía queda pendiente de confirmación profesional (mismo patrón que ya existía para las metas de recuperación).

Verificado con 18 pruebas nuevas: fórmulas de puntaje con casos de borde exactos en cada corte, el algoritmo de riesgo completo, render del panel con el formulario nuevo, y el flujo de autorreporte del portal de punta a punta.

## Etapa 1 — brechas cerradas frente al prompt maestro más reciente (2026-09-09)

El nuevo prompt maestro pedía explícitamente, para la Etapa 1, un **pasaporte de recuperación** y el ciclo **"el paciente reporta una dificultad → el profesional responde → el paciente ve la respuesta"**. Ninguno existía todavía; ya están implementados:

- **Pasaporte de recuperación** (`/pics → Pasaporte de recuperación`, `/portal/pasaporte` "Antes y ahora"): estado previo al ingreso (movilidad, autonomía, actividades habituales, apoyos), situación actual, barreras (hogar/transporte/acompañamiento/acceso) y una lista de necesidades/objetivos en las propias palabras del paciente (ej. "quiero volver a cocinar"). Registra quién lo reportó y cuándo, con confirmación profesional separada. El portal muestra el "antes" junto al "ahora" (último seguimiento registrado) **sin calcular ningún porcentaje de recuperación** — la comparación la hace quien lo lee.
- **Solicitudes y dificultades** (`/pics → Solicitudes y dificultades`, `/portal/ayuda` "Necesito ayuda"): el paciente o el cuidador reportan una dificultad con la prioridad que ellos perciben; el profesional la asigna, reconoce recepción, responde, escala o resuelve; el paciente ve la respuesta en su propia sesión, sin ver a quién se asignó internamente.
- **Motivo estructurado de dificultad** al reportar un avance de meta (cansancio, dolor, falta de ayuda, dificultad para comprender, otra), en vez de solo un sí/no.

Verificado con 16 pruebas nuevas, incluyendo el ciclo completo de la solicitud (reporta → responde → el paciente consulta) y el aislamiento entre casos (un cuidador de un caso no ve el pasaporte ni las solicitudes de otro).

## Qué quedó simulado o pendiente (no construido todavía)

Quedan para la Etapa 2 (según el prompt maestro más reciente): plan interdisciplinario formal con versiones, ruta propia del cuidador (más allá del diario), preparación para el alta con recorrido de comprensión, agenda coordinada interna. Para etapas posteriores: medicamentos conciliados, educación personalizada, configuración institucional/academia, integraciones reales (agendas externas, dispositivos) y resúmenes asistidos por IA. Tampoco hay fotos/audio en el diario (solo texto, como pide explícitamente el prompt), ni estados formales del episodio (UCI → hospitalización → egreso) — el episodio hoy es un único caso PICS sin sub-estados de estancia.

El paciente **no puede escribir** en el diario todavía (solo leer) — así lo pide explícitamente el prompt maestro para esta iteración ("participar posteriormente").

## Restricción de entorno detectada

Este equipo no tiene Node.js instalado (solo se copió `node_modules`, sin el runtime). El portal usa Bootstrap 5 y Livewire, ambos ya funcionan sin recompilar assets — pero si en el futuro se necesita Tailwind o JS nuevo por fuera de esas dos librerías, hará falta instalar Node.js y correr `npm run build`.

## Modelo de datos nuevo (además de lo ya documentado para PICS)

`patients` (+ columnas de login), `caregivers`, `caregiver_authorizations`, `diary_entries`, `recovery_goals`, `goal_progress_reports` — todas ancladas a `pics_cases` (el "episodio" del paciente). Detalle completo de columnas en la migración `database/migrations/2026_09_08_100000_create_posuci_iteration1_tables.php`.

## Decisión pendiente para la próxima sesión

Antes de avanzar a la Etapa 2, conviene que confirmes: **¿qué estados del episodio necesitas primero** (UCI/hospitalización/egreso/seguimiento) **y si el egreso debe seguir dependiendo de que un profesional lo confirme** (así lo asume el diseño actual, coherente con el prompt maestro). También conviene decidir si el `SupportRequest` de la Etapa 1 debe extenderse a más tipos de solicitud (no solo "dificultad") a medida que avancen las etapas, o si cada módulo futuro (medicamentos, citas) tendrá su propio flujo de solicitud.
