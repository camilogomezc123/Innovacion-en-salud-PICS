# POSUCI 360 Conecta — Estado del proyecto

Última actualización: 2026-09-13 (+ el paciente ya puede escribir en su diario).

## El paciente puede escribir en el diario (2026-09-13)

Hasta ahora el diario era de una sola vía: el cuidador escribía, el paciente solo leía (así lo pedía explícitamente el prompt maestro para la Iteración 1, "participar posteriormente"). Ahora el paciente también puede escribir sus propias entradas desde `/portal/diario` — mismo formulario, pero sin los campos "mensaje para el paciente" y "¿el paciente puede leerla?" (no aplican cuando el propio paciente escribe: su entrada siempre le es visible a él mismo). El cuidador sigue viendo todas las entradas del caso, incluidas las del paciente, sin ningún cambio en su flujo.

De paso corregí un bug que esto habría expuesto: la vista y la pestaña del caso en `/pics` mostraban el autor con `$entry->authorable->name`, pero `Patient` guarda el nombre en `full_name`, no en `name` — una entrada del paciente se habría visto con el autor en blanco. Se centralizó en `DiaryEntry::authorLabel()` para no repetir ese detalle en cada vista.

Verificado con 1 prueba nueva (208 en total, todas en verde): el paciente escribe, la entrada queda con `visible_to_patient = true` automáticamente, se ve con su nombre correcto, y el cuidador también la ve.

## Etapa 3 — educación personalizada (2026-09-13)

Última pieza de la Etapa 3. Hasta ahora la "educación" solo existía como texto libre repetido por caso (el campo `medications_review` del seguimiento, o las instrucciones de `DischargeReadinessItem`). Ahora hay un **catálogo reutilizable** (`/pics → Educación personalizada`, `EducationResource`): el staff redacta un contenido una sola vez (título, categoría — respiratorio/movilidad/cognitivo/emocional/nutrición/cuidador —, dirigido a paciente/cuidador/ambos, cuerpo de texto) y luego lo **asigna** a los casos donde aplica desde la pestaña "Educación personalizada" de cada caso, con una nota opcional de por qué aplica a ese paciente en particular — eso es lo "personalizado": la biblioteca es compartida, la selección es por paciente.

El portal (`/portal/educacion`) muestra solo el contenido asignado y activo, agrupado por categoría, con un botón "Marcar como leído". A diferencia de "Preparación para el alta", aquí no hay verificación profesional de comprensión — es material de consulta continua, no un requisito de egreso.

Verificado con 6 pruebas automatizadas nuevas (207 en total, todas en verde): creación del catálogo, asignación con atribución server-side, el portal solo muestra contenido activo (uno inactivo asignado no aparece), marcar como leído, aislamiento entre casos, y render real de las páginas del recurso y la pestaña del caso en `/pics`.

**Con esto se cierra la Etapa 3 completa**: medicamentos conciliados, monitoreo en casa (registro manual) y educación personalizada.

## Etapa 3 — monitoreo en casa (2026-09-11)

El usuario pidió avanzar con "integraciones externas" (dispositivos de monitoreo), pero confirmó que **no hay hoy ninguna API ni credencial real** de ningún proveedor. Construir una integración contra un sistema que no existe habría sido fabricar una conexión falsa, en contra de la convención del proyecto de no inventar funcionalidad ni datos. Se construyó en cambio lo que sí es real hoy: un **registro manual de lecturas** (`/portal/monitoreo`, pestaña "Monitoreo en casa" de solo lectura en `/pics → Casos → [caso]`) — saturación, frecuencia cardíaca, presión arterial, temperatura, frecuencia respiratoria, glucosa o peso, digitadas por el paciente o el cuidador. Sin reglas de "rango normal" ni alertas automáticas — eso sería inventar una regla clínica no pedida; el staff interpreta los números. Cuando exista una API real de algún proveedor, este modelo de datos (`HomeMonitoringReading`) es el punto de partida natural para poblarlo automáticamente en vez de a mano.

Verificado con 4 pruebas automatizadas nuevas (201 en total, todas en verde): paciente y cuidador autorizado registran lecturas con atribución server-side, la pestaña de solo lectura renderiza en el caso, y el aislamiento entre casos.

## Etapa 3 — medicamentos conciliados (2026-09-11)

Primera pieza de la Etapa 3 (elegida por el usuario entre medicamentos, educación personalizada e integraciones): **conciliación de medicamentos** (`/pics → Medicamentos conciliados`, `/portal/medicamentos`). Antes solo existía un campo de texto libre dentro del seguimiento (`PicsFollowup.medications_review`) y el tema genérico "medicamentos" en la preparación de alta (que solo mide si el paciente entendió el tema, no el detalle clínico). Ahora hay una lista real por medicamento: nombre, dosis, vía, frecuencia y la **decisión de conciliación** (continúa igual / nuevo / suspendido / dosis ajustada), documentada por el staff. El portal la muestra en modo lectura — **no es una herramienta de prescripción**, es trazabilidad para que el paciente/familia sepan qué tomar.

Si el paciente o el cuidador tiene una duda sobre un medicamento, no se agregó una tercera capa de "revisado/confirmado": se reutiliza el `SupportRequest` ya construido en la Etapa 1 con un tipo nuevo (`duda_medicamento`) — el enlace "Tengo una duda sobre este medicamento" lleva directo a "Necesito ayuda" con el tipo y la descripción ya prellenados con el nombre del medicamento. Es la primera aplicación concreta de la decisión ya tomada de extender `SupportRequest` con más tipos en vez de construir un flujo de solicitud paralelo por módulo.

Verificado con 5 pruebas automatizadas nuevas (197 en total, todas en verde) y un recorrido manual completo: conciliación creada desde `/pics`, vista en el portal, y el flujo "tengo una duda" confirmado de punta a punta contra el servidor real (el snapshot de Livewire mostró el tipo y la descripción prellenados correctamente).

## Etapa 2 (2026-09-10)

Cierra las 5 brechas pendientes de la Etapa 2 del prompt maestro:

- **Estados formales del episodio**: cada caso PICS ahora avanza por **UCI → Hospitalización → Egreso → Seguimiento** (`clinical_stage`, visible como badge en el caso). Es un concepto distinto del "Estado del caso" (`status`, el flujo de auditoría/revisión, ya existente). El avance es secuencial (no se pueden saltar etapas salvo el líder/administrador) y el **egreso nunca es automático**: solo lo puede confirmar el líder/administrador o un médico del equipo, desde la acción dedicada "Confirmar egreso" — nunca como efecto de otro cambio en el formulario. Cada etapa registra su propia fecha (incluida quién confirmó el egreso).
- **Plan interdisciplinario formal con versiones** (`/pics → Plan interdisciplinario`): objetivo general, criterios de egreso y el aporte de cada disciplina (medicina, enfermería, terapias, psicología, trabajo social, nutrición). Cada vez que se edita, la versión anterior queda archivada completa con su vigencia — igual que ya funcionaba para las fichas técnicas de indicadores — así un plan histórico nunca se reescribe en silencio.
- **Ruta propia del cuidador** (`/portal/ruta-cuidador`, exclusiva del cuidador — nueva pestaña "Ruta del cuidador" en el caso): pasos de orientación, autocuidado, red de apoyo y preparación para el manejo en casa que el cuidador completa a su ritmo y el profesional confirma. Requiere una autorización propia (`can_access_journey`), independiente de poder escribir en el diario.
- **Preparación para el alta con recorrido de comprensión** (`/pics → Preparación para el alta`, `/portal/preparacion-alta`): temas como medicamentos, signos de alarma, citas de control y cuidados en casa. Dos capas independientes: el paciente/cuidador marca "ya lo revisé" desde el portal, y el profesional verifica la comprensión real por teach-back — esta segunda capa no depende de que el portal se haya usado.
- **Agenda coordinada interna** (`/pics → Agenda coordinada`): lista cronológica (no un calendario — este entorno no tiene Node.js para cargar una librería de calendario) que combina remisiones y tareas/citas/recordatorios internos de todos los casos, agrupada por fecha, con acciones rápidas para completar o cancelar.

Verificado con 28 pruebas automatizadas nuevas (192 en total, todas en verde) y un recorrido manual completo contra el servidor real.

## Cierre de los 5 huecos del portal (2026-09-09)

Hasta ahora no existía ninguna forma de **crear o autorizar un cuidador desde la interfaz** (solo por seeder o pruebas), y las alertas de la trazabilidad del portal eran solo indicadores pasivos. Se cierran cinco huecos relacionados:

- **Alta y autorización real de cuidadores**: nueva pestaña "Familia y cuidadores autorizados" dentro de cada caso PICS (`/pics → Casos → [caso]`). El profesional busca o crea el cuidador, define el parentesco y si puede escribir en el diario; puede revocar el acceso en cualquier momento.
- **Invitación real por correo**: la acción "Enviar invitación" (para el cuidador) y "Configurar acceso del paciente" (en la cabecera del caso) generan una contraseña temporal, la guardan hasheada y envían un correo con las credenciales (`MAIL_MAILER=log` en este entorno: nada sale a un correo real, todo queda en `storage/logs/laravel.log`, coherente con "solo pacientes ficticios en desarrollo").
- **"Olvidé mi contraseña"** (`/portal/olvide-password`): funciona igual para paciente y cuidador, con brokers de recuperación propios (`patients`/`caregivers`) y su propia tabla de tokens — responde siempre el mismo mensaje exista o no la cuenta, para no revelar si un correo está registrado.
- **Cambio de contraseña obligatorio en el primer ingreso**: cualquier cuenta invitada queda marcada `must_change_password = true`; al iniciar sesión se le redirige a `/portal/cambiar-contrasena` antes de poder usar el resto del portal. Las cuentas de demostración documentadas abajo **no** tienen esta bandera activa, para que se pueda entrar directo con ellas.
- **Alertas de inactividad y tendencia semanal** en `/pics/trazabilidad-portal`: además del resumen agregado ya existente, ahora se listan los casos que necesitan atención (cuidador autorizado que nunca ha entrado tras 3 días, o caso sin ninguna actividad de portal en 15 días) y una gráfica de barras (HTML/CSS, sin librerías nuevas) con la actividad de las últimas 8 semanas.

Verificado con 164 pruebas automatizadas (11 nuevas: relation manager de cuidadores, invitación, recuperación de contraseña para ambos guards, cambio forzado de punta a punta, alertas y tendencia) y un recorrido manual completo contra el servidor real vía HTTP: invitación → correo confirmado en `storage/logs/laravel.log` → login con la contraseña temporal → redirección forzada a cambiar contraseña → cambio → acceso normal al portal, con la base de datos verificada en cada paso.

## Trazabilidad del portal (`/pics/trazabilidad-portal`, 2026-09-09)

Conecta el panel profesional con el uso real de `/portal`: los logins de paciente y cuidador ahora se registran de verdad (antes la columna existía pero nunca se llenaba). El módulo nuevo mide, por caso y en agregado institucional: si el cuidador está autorizado y ha entrado, último ingreso del paciente, entradas de diario, reportes de metas por autor (paciente vs. cuidador), autorreportes de "Cómo me siento", solicitudes de ayuda respondidas y tiempo de respuesta, y estado del pasaporte de recuperación. Visible también como pestaña dentro de cada caso y como enlace desde `/pics/indicadores`. Es un eje de medición de **uso de la plataforma**, distinto de los indicadores clínicos PICS.

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

Para etapas posteriores (según el prompt maestro más reciente): configuración institucional/academia, integraciones reales con proveedores de dispositivos (hoy el monitoreo en casa es un registro manual, no una API conectada) y resúmenes asistidos por IA. Tampoco hay fotos/audio en el diario (solo texto, como pide explícitamente el prompt). La agenda coordinada es una lista cronológica, no un calendario visual — no hay Node.js en este entorno para cargar una librería de calendario.

## Restricción de entorno detectada

Este equipo no tiene Node.js instalado (solo se copió `node_modules`, sin el runtime). El portal usa Bootstrap 5 y Livewire, ambos ya funcionan sin recompilar assets — pero si en el futuro se necesita Tailwind o JS nuevo por fuera de esas dos librerías, hará falta instalar Node.js y correr `npm run build`.

## Modelo de datos nuevo (además de lo ya documentado para PICS)

`patients` (+ columnas de login), `caregivers`, `caregiver_authorizations`, `diary_entries`, `recovery_goals`, `goal_progress_reports` — todas ancladas a `pics_cases` (el "episodio" del paciente). Detalle completo de columnas en la migración `database/migrations/2026_09_08_100000_create_posuci_iteration1_tables.php`. Para la Etapa 2: `care_plans`/`care_plan_versions`, `caregiver_journey_steps`, `discharge_readiness_checks`/`discharge_readiness_items`, `pics_agenda_items`, más `clinical_stage` (y sus fechas) en `pics_cases` y `can_access_journey` en `caregiver_authorizations` — ver las migraciones fechadas `2026_09_10_*`. Para la Etapa 3: `medication_reconciliations`/`medication_reconciliation_items` (`2026_09_11_000000_create_medication_reconciliations_table.php`), `home_monitoring_readings` (`2026_09_12_000000_create_home_monitoring_readings_table.php`) y `education_resources`/`education_assignments` (`2026_09_13_000000_create_education_resources_table.php`).

## Decisión confirmada para la Etapa 2

El usuario confirmó (2026-09-08): el ciclo completo **UCI → Hospitalización → Egreso → Seguimiento**, con el egreso dependiendo siempre de confirmación profesional explícita; y que `SupportRequest` se extienda con más tipos en el futuro en vez de construir un flujo de solicitud paralelo por módulo (esta Etapa 2 no le agregó tipos nuevos todavía — ninguna de sus 5 piezas lo requería).

## Decisiones confirmadas para la Etapa 3

El usuario confirmó (2026-09-11): "Confirmar egreso" se queda como recomendación, no como bloqueo. Eligió, en orden: **medicamentos conciliados**, luego **integraciones externas** — pero al confirmar que no existe ninguna API/credencial real de ningún proveedor de dispositivos, se construyó el registro manual de monitoreo en su lugar — y por último **educación personalizada**, con lo que la Etapa 3 queda completa.

## Decisión pendiente para la próxima sesión

Con la Etapa 3 completa y el paciente ya escribiendo en su diario, falta decidir qué sigue del prompt maestro: **configuración institucional/academia** (panel para que la institución ajuste catálogos/plantillas/roles propios) o **resúmenes asistidos por IA**. También sigue abierto si alguna vez aparece una API real de un proveedor de dispositivos, conectarla para poblar `home_monitoring_readings` automáticamente en vez del registro manual.
