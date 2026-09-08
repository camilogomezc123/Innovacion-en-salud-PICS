# POSUCI 360 Conecta — Estado del proyecto

Última actualización: 2026-09-08 (Iteración 1 completada).

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

## Qué quedó simulado o pendiente (no construido todavía)

Por diseño, esta iteración no incluye (quedan para la Iteración 2 según el prompt maestro): estados formales del episodio (UCI → hospitalización → egreso), bienestar emocional ("Cómo me siento"), plan interdisciplinario completo, agenda de citas, tareas/alertas con escalamiento, ni integración de dispositivos. Tampoco hay fotos/audio en el diario (solo texto, como pide la Iteración 1), ni pantalla de "Mi actividad".

El paciente **no puede escribir** en el diario todavía (solo leer) — así lo pide explícitamente el prompt maestro para esta iteración ("participar posteriormente").

## Restricción de entorno detectada

Este equipo no tiene Node.js instalado (solo se copió `node_modules`, sin el runtime). El portal usa Bootstrap 5 y Livewire, ambos ya funcionan sin recompilar assets — pero si en el futuro se necesita Tailwind o JS nuevo por fuera de esas dos librerías, hará falta instalar Node.js y correr `npm run build`.

## Modelo de datos nuevo (además de lo ya documentado para PICS)

`patients` (+ columnas de login), `caregivers`, `caregiver_authorizations`, `diary_entries`, `recovery_goals`, `goal_progress_reports` — todas ancladas a `pics_cases` (el "episodio" del paciente). Detalle completo de columnas en la migración `database/migrations/2026_09_08_100000_create_posuci_iteration1_tables.php`.

## Decisión pendiente para la próxima sesión

Antes de avanzar a la Iteración 2, conviene que confirmes: **¿qué estados del episodio necesitas primero** (UCI/hospitalización/egreso/seguimiento) **y si el egreso debe seguir dependiendo de que un profesional lo confirme** (así lo asume el diseño actual, coherente con el prompt maestro).
