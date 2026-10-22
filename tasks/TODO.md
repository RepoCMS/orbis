# tasks/TODO.md — Backlog Abierto

> **Fuentes:** CONTEXT.md §5 (Próximos Pasos), §4 (Blockers), docs/history.md §1-2, docs/architecture.md §10, CHANGELOG.md [Unreleased].
> **Actualizado:** 2026-08-07
> **Etiquetas:** `[TODO-REAL]` = tarea verificada en la fuente · `[TODO: validar]` = requiere verificación antes de ejecutar (no está 100% confirmada).

---

## Backlog

| ID | Tarea | Prioridad | BC | Fuente | Estado |
|----|-------|-----------|----|--------|--------|
| T04 | - [ ] ~~Configurar **remote GitHub**~~ → ✅ Completado 2026-08-06: `https://github.com/RepoCMS/orbis.git`, rama `main` (ver DONE.md) | ALTA | infra | git / DONE.md | ✅ |
| T05 | - [ ] ~~Verificar si remote GitHub ya fue configurado~~ → ✅ Verificado 2026-08-06: sí (T04 cerrado) | MEDIA | infra | git / DONE.md | ✅ |
| T06 | - [ ] Decidir **bcrypt vs argon2** para password hashing (issue #42) | MEDIA | Identity | CONTEXT.md §4, docs/history.md | [TODO-REAL] |
| T07 | - [ ] Implementar **Identity Dashboard KPIs** (6 KPIs, docs/analytics.md F.1.2) + `IdentityDashboardController` + template + `IdentityDashboardMetricsHandler` | MEDIA | Identity | CONTEXT.md §5, docs/analytics.md F.1.2 | [TODO-REAL] |
| T08 | - [ ] Escribir **tests para Location BC** (hoy 7 tests, API-only, sin interfaz web) | MEDIA | Location | CONTEXT.md §2/§3 `005-location`, docs/history.md #3 | [TODO-REAL] |
| T09 | - [ ] Migrar **prefijos de tablas** de Inventory BC (`stock_`/`serial_`/`inventory_` → `{bc}_`) según ADR-013 | MEDIA | Inventory | CHANGELOG.md ADR-013, docs/history.md deuda #7 | [TODO-REAL] |
| T10 | - [ ] Verificar wiring y tests de **`SendNotification`** (reportado sin caller ni tests) | MEDIA | Notification | docs/history.md §1 #4 | [TODO: validar] |
| T11 | - [ ] Configurar **provider de email real** para forgot-password (hoy `LoggerForgotPasswordEmailSender` + `MAILER_DSN=null://null`) | MEDIA | Identity | docs/history.md §1 #1 | [TODO-REAL] |
| T12 | - [ ] Indexar **ADR-011..013** en `docs/adr/README.md` (hoy el índice solo cubre 001-008) | BAJA | docs | filesystem (verificado 2026-08-06) | [TODO-REAL] |
| T13 | - [ ] Resolver ausencia de **ADR-009 y ADR-010** (saltan de 008 a 011 — ¿se perdieron o nunca existieron?) | BAJA | docs | docs/adr/ filesystem | [TODO: validar] |
| T14 | - [ ] Auditoría de **comparaciones de enums en Twig** (validar lowercase `in_transit` etc.) | BAJA | Cross-BC | docs/history.md §1 #5 | [TODO-REAL] |
| T15 | - [ ] Decidir si **Location BC requiere interfaz web** (hoy API-only) | BAJA | Location | docs/history.md §1 #3 | [TODO-REAL] |
| T16 | - [ ] Cerrar deuda de naming: factory methods (`create`/`register`/`hire`/`add`), controllers (`LocationController` vs `Api{BC}{Entity}Controller`), pattern de Domain Events dual | BAJA | Cross-BC | docs/history.md §2 #4-6 | [TODO-REAL] |
| T17 | - [ ] Refresh token cleanup automatizado (cron sobre `identity:cleanup-refresh-tokens`) | BAJA | Identity | docs/architecture.md §10 | [TODO-REAL] |
| T18 | - [ ] Rate limiting en login (anti fuerza bruta) | BAJA | Identity | docs/architecture.md §10 | [TODO-REAL] |
| T19 | - [ ] Password expiration notification | BAJA | Identity | docs/architecture.md §10 | [TODO-REAL] |
| T20 | - [ ] Reconciliar conteo de tests (CONTEXT.md: 452 test files vs CHANGELOG.md: 384 files / 1629 métodos) | BAJA | docs | CONTEXT.md §1 vs CHANGELOG.md [Unreleased] | [TODO: validar] |
| T21 | - [ ] ~~Completar `docs/core/`, `docs/standards/` y `.ai/`~~ → ✅ Completado 2026-08-06; `.ai/` eliminado 2026-10-22 (ver DONE.md y docs/history.md §7) | ALTA | docs | plan de transformación 2026-08-06 | ✅ |
| T22 | - [ ] ~~Sync `AGENTS.md`/`CONTEXT.md` con tablero `tasks/`~~ → ✅ Completado 2026-08-06 (ver DONE.md) | BAJA | docs | CONTEXT.md §0 | ✅ |
| T23 | - [ ] Migrar documentación existente en inglés a español (convención §Idioma de AGENTS.md): `docs/adr/README.md`, `docs/adr/ADR-001..008`, títulos de `docs/modules/*.md` | MEDIA | docs | AGENTS.md §Idioma (regla 1) | [TODO-REAL] |
| T24 | - [ ] Migrar enum values UPPERCASE a lowercase con migración de datos (AuthEvent, UserTokenType) — detectado por `bin/validate-project.php` (baseline regla2) | MEDIA | Identity | validate-baseline.txt, specs/014-governance §6 | [TODO-REAL] |
| T25 | - [ ] Migrar enum values en español/acentos a lowercase snake_case (NonserializedStockStatus, SerializedStockStatus) con migración de datos — detectado por validador | MEDIA | Inventory | validate-baseline.txt | [TODO-REAL] |
| T26 | - [ ] Renombrar VOs históricos sin sufijo ValueObject (`PurchaseSuggestion`, `ProcessedCommandId`) — detectado por validador (baseline regla9) | BAJA | Cross-BC | validate-baseline.txt | [TODO-REAL] |
| T27 | - [ ] Reducir baseline PHPStan (159 errores nivel 5) en ciclos de a ~20 — objetivo nivel 9 | BAJA | Cross-BC | phpstan-baseline.neon, specs/014-governance §6 | [TODO-REAL] |
| T28 | - [ ] Actualizar dependencias con advisories: dompdf (<3.1.6, 3 CVEs medium) + 2 paquetes más — `composer audit` (10 advisories, 2026-08-06) | MEDIA | infra | composer audit, .github/workflows/security.yml | [TODO-REAL] |
| T29 | - [ ] Implementar coverage de Domain ≥ 80% en CI (DD-3 del spec 014) — requiere Xdebug/PCOV + job de coverage en ci.yml | MEDIA | infra | specs/014-governance/spec.md §3 DD-3 | [TODO-REAL] |
| T32 | - [x] **Corregir punto ciego de `bin/wiring-check.php`** ✅ Completado 2026-08-12: stem-based matching (Query↔Handler, Command↔Handler) detecta 22 orfanos correctamente. Ver commit en DONE.md. | ALTA | infra | hallazgo QA-Reviewer 2026-08-07, CONTEXT.md §7 B8 | ✅ |
| T33 | - [x] **Notification G-03 (retry no re-despacha)** ✅ Completado 2026-08-12: `RetryNotificationHandler` inyecta `MessageBusInterface` y despacha `SendNotificationMessage`. Tests actualizados (5/5 verdes). G-01/G-02/G-04/G-05 pendientes en backlog. | ALTA | Notification | specs/006-notification/spec.md, CONTEXT.md §2 | ✅ (parcial G-03) |
| T34 | - [x] **Organization G-07/G-08/G-09** ✅ Completado 2026-08-12: `EmploymentPeriod::start()` en Create/Reactivate, `SpecialPeriodAutoProcessed` publicado desde cron `org:process-special-periods`, 3 archivos basura eliminados. G-10..G-14 pendientes. | MEDIA | Organization | specs/001-organization-spec/spec.md §9-10, CONTEXT.md §2 | ✅ (parcial G-07..G-09) |
| T35 | - [x] **Client G-10/G-11** ✅ Completado 2026-08-12: bug SQL `contacts`→`client_contacts` corregido en dashboard, `ListContactsByClientQuery` reparada (filtra por clientId). G-06 pendiente. | MEDIA | Client | specs/002-client/spec.md, CONTEXT.md §2 | ✅ (parcial G-10..G-11) |
| T35-G06 | - [x] **Tests para handlers de Client** ✅ Completado 2026-08-17: `ImportModelPartsHandlerTest.php` (6 test cases: happy path, dry-run, duplicate skip, invalid rows, empty file, missing headers) | MEDIA | Client | specs/002-client/spec.md §G-06 | ✅ |

---

## Instrucciones de mantenimiento

1. **Todo agente que cierre una tarea** mueve la fila a `tasks/DONE.md` (tabla `| Fecha | Tarea | Fuente |`) y la elimina de esta tabla.
2. Una tarea solo se marca como cerrada **verificada** (tests verdes, wiring con CodeGraph, o evidencia en git/filesystem). No por "parece que está".
3. Nueva tarea detectada → agregar fila con ID correlativo, prioridad, BC y **fuente** (archivo + sección). Sin fuente, no entra al tablero.
4. Las tareas `[TODO: validar]` se convierten en `[TODO-REAL]` cuando se confirma la información en el repo; si la confirmación desmiente la tarea, se elimina con nota.
5. Las prioridades se ajustan siguiendo CONTEXT.md §10; cambios grandes de dirección los decide el Planner.
