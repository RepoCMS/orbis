# tasks/TODO.md — Backlog Abierto

> **Fuentes:** CONTEXT.md §10 (Próximos Pasos), §7 (Blockers), TODOS.md, docs/history.md §1-2, HANDOFF.md §13, PROJECT_CONTEXT.md §7, CHANGELOG.md [Unreleased].
> **Actualizado:** 2026-08-06
> **Etiquetas:** `[TODO-REAL]` = tarea verificada en la fuente · `[TODO: validar]` = requiere verificación antes de ejecutar (no está 100% confirmada).

---

## Backlog

| ID | Tarea | Prioridad | BC | Fuente | Estado |
|----|-------|-----------|----|--------|--------|
| T01 | - [ ] Completar spec de **Notification BC** (~60% → objetivo 95%) — cross-BC wiring pendiente | ALTA | Notification | CONTEXT.md §10 #4, §3 `006-notification` | [TODO-REAL] |
| T02 | - [ ] Completar spec de **Organization BC** (~67% → objetivo 95%) — reportes P2 pendientes | ALTA | Organization | CONTEXT.md §10 #5, §3 `001-organization-spec` | [TODO-REAL] |
| T03 | - [ ] Completar spec de **Client BC** (~70% → objetivo 95%) — importers P2/P3 pendientes | ALTA | Client | CONTEXT.md §10 #6, §3 `002-client` | [TODO-REAL] |
| T04 | - [ ] ~~Configurar **remote GitHub**~~ → ✅ Completado 2026-08-06: `https://github.com/RepoCMS/orbis.git`, rama `main` (ver DONE.md) | ALTA | infra | HANDOFF.md §13 (actualizado) | ✅ |
| T05 | - [ ] ~~Verificar si remote GitHub ya fue configurado~~ → ✅ Verificado 2026-08-06: sí (T04 cerrado) | MEDIA | infra | HANDOFF.md §13 (actualizado) | ✅ |
| T06 | - [ ] Decidir **bcrypt vs argon2** para password hashing (issue #42) | MEDIA | Identity | CONTEXT.md §7, TODOS.md blockers | [TODO-REAL] |
| T07 | - [ ] Implementar **Identity Dashboard KPIs** (6 KPIs, docs/analytics.md F.1.2) + `IdentityDashboardController` + template + `IdentityDashboardMetricsHandler` | MEDIA | Identity | CONTEXT.md §10 #7, TODOS.md P1 (tareas 1-3, 5) | [TODO-REAL] |
| T08 | - [ ] Escribir **tests para Location BC** (hoy 7 tests, API-only, sin interfaz web) | MEDIA | Location | CONTEXT.md §2/§3 `005-location`, docs/history.md #3 | [TODO-REAL] |
| T09 | - [ ] Migrar **prefijos de tablas** de Inventory BC (`stock_`/`serial_`/`inventory_` → `{bc}_`) según ADR-013 | MEDIA | Inventory | CHANGELOG.md ADR-013, docs/history.md deuda #7 | [TODO-REAL] |
| T10 | - [ ] Verificar wiring y tests de **`SendNotification`** (reportado sin caller ni tests) | MEDIA | Notification | docs/history.md §1 #4 | [TODO: validar] |
| T11 | - [ ] Configurar **provider de email real** para forgot-password (hoy `LoggerForgotPasswordEmailSender` + `MAILER_DSN=null://null`) | MEDIA | Identity | docs/history.md §1 #1 | [TODO-REAL] |
| T12 | - [ ] Indexar **ADR-011..013** en `docs/adr/README.md` (hoy el índice solo cubre 001-008) | BAJA | docs | filesystem (verificado 2026-08-06) | [TODO-REAL] |
| T13 | - [ ] Resolver ausencia de **ADR-009 y ADR-010** (saltan de 008 a 011 — ¿se perdieron o nunca existieron?) | BAJA | docs | docs/adr/ filesystem | [TODO: validar] |
| T14 | - [ ] Auditoría de **comparaciones de enums en Twig** (validar lowercase `in_transit` etc.) | BAJA | Cross-BC | docs/history.md §1 #5 | [TODO-REAL] |
| T15 | - [ ] Decidir si **Location BC requiere interfaz web** (hoy API-only) | BAJA | Location | docs/history.md §1 #3 | [TODO-REAL] |
| T16 | - [ ] Cerrar deuda de naming: factory methods (`create`/`register`/`hire`/`add`), controllers (`LocationController` vs `Api{BC}{Entity}Controller`), pattern de Domain Events dual | BAJA | Cross-BC | docs/history.md §2 #4-6 | [TODO-REAL] |
| T17 | - [ ] Refresh token cleanup automatizado (cron sobre `identity:cleanup-refresh-tokens`) | BAJA | Identity | PROJECT_CONTEXT.md §7 | [TODO-REAL] |
| T18 | - [ ] Rate limiting en login (anti fuerza bruta) | BAJA | Identity | PROJECT_CONTEXT.md §7 | [TODO-REAL] |
| T19 | - [ ] Password expiration notification | BAJA | Identity | PROJECT_CONTEXT.md §7 | [TODO-REAL] |
| T20 | - [ ] Reconciliar conteo de tests (CONTEXT.md: 452 test files vs CHANGELOG.md: 384 files / 1629 métodos) | BAJA | docs | CONTEXT.md §1 vs CHANGELOG.md [Unreleased] | [TODO: validar] |
| T21 | - [ ] ~~Completar `docs/core/`, `docs/standards/` y `.ai/`~~ → ✅ Completado 2026-08-06 (ver DONE.md) | ALTA | docs | plan de transformación 2026-08-06 (ver IN_PROGRESS.md) | ✅ |
| T22 | - [ ] ~~Sync `AGENTS.md`/`PROJECT_CONTEXT.md` con tablero `tasks/`~~ → ✅ Completado 2026-08-06 (ver DONE.md) | BAJA | docs | PROJECT_CONTEXT.md §6.1 vs filesystem | ✅ |
| T23 | - [ ] Migrar documentación existente en inglés a español (convención §Idioma de AGENTS.md): `PROJECT_CONTEXT.md`, `docs/adr/README.md`, `docs/adr/ADR-001..008`, títulos de `docs/modules/*.md` | MEDIA | docs | AGENTS.md §Idioma (regla 1) | [TODO-REAL] |

---

## Instrucciones de mantenimiento

1. **Todo agente que cierre una tarea** mueve la fila a `tasks/DONE.md` (tabla `| Fecha | Tarea | Fuente |`) y la elimina de esta tabla.
2. Una tarea solo se marca como cerrada **verificada** (tests verdes, wiring con CodeGraph, o evidencia en git/filesystem). No por "parece que está".
3. Nueva tarea detectada → agregar fila con ID correlativo, prioridad, BC y **fuente** (archivo + sección). Sin fuente, no entra al tablero.
4. Las tareas `[TODO: validar]` se convierten en `[TODO-REAL]` cuando se confirma la información en el repo; si la confirmación desmiente la tarea, se elimina con nota.
5. Las prioridades se ajustan siguiendo CONTEXT.md §10; cambios grandes de dirección los decide el Planner.
