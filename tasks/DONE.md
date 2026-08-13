# tasks/DONE.md — Trabajo Completado

> **Actualizado:** 2026-08-12
> Tabla cronológica de tareas/hitos cerrados. Fuente = evidencia (git commit, CONTEXT.md, docs/history.md, CHANGELOG.md).

---

## Completados

| Fecha | Tarea | Fuente |
|-------|-------|--------|
| 2026-08-12 | **T32 — Fix punto ciego `bin/wiring-check.php`**: stem-based matching (Query↔Handler, Command↔Handler) reemplaza `str_contains`; ahora detecta 22 orfanos correctamente (antes falsos negativos). Verificado local: `bin/wiring-check.php` exit 1, 22 orfanos listados | filesystem: `bin/wiring-check.php` líneas 122-144; wiring 22 orfanos detectados |
| 2026-08-12 | **T33 — Notification G-03 (retry re-despacha)**: `RetryNotificationHandler` inyecta `MessageBusInterface` y despacha `SendNotificationMessage` tras `retry()`+`save()`. Tests actualizados (5/5 verdes): mock de bus + assert dispatch con `Envelope`. G-01/G-02/G-04/G-05 pendientes en T33 (backlog) | filesystem: `RetryNotificationHandler.php` + `ApiNotificationLogControllerTest.php`; phpunit 5/5 OK |
| 2026-08-12 | **T34 — Organization G-07/G-08/G-09**: `EmploymentPeriod::start()` invocado en `CreateEmployeeHandler` (startedAt=hiredAt) y `ReactivateEmployeeHandler` (startedAt=now); `SpecialPeriodAutoProcessed` publicado desde cron `org:process-special-periods` (transiciones started/completed); 3 archivos basura `*-DESKTOP-GU1L412.php` eliminados de tests. G-10..G-14 pendientes | filesystem: `CreateEmployeeHandler.php`, `ReactivateEmployeeHandler.php`, `ProcessEmployeeSpecialPeriodsCommand.php`, `git rm` 3 archivos; spec 001 actualizado |
| 2026-08-12 | **T35 — Client G-10/G-11**: bug SQL `contacts`→`client_contacts` corregido en `GetClientDashboardSummaryHandler::countActiveContacts()` + `contactsByStatus()`; `ListContactsByClientQuery` reparada (ahora acepta `clientId`, handler usa `findByClientId`). G-06 (tests de 13 handlers) pendiente | filesystem: `GetClientDashboardSummaryHandler.php`, `ListContactsByClientQuery.php`, `ListContactsByClientHandler.php`; spec 002 actualizado |
| 2026-08-07 | **T30 — Fix G-06 (crítico) Organization**: `ListEmployeesQuery` ampliado (`serviceZoneId`/`page`/`limit`), `ListEmployeesResult` nuevo, repo `findByFilters` paginado + `countByFilters`, handler devuelve Result, 6 callers usan `->items`, API `GET /employees` paginado + filtros `profile/status/search(q)/serviceZoneId`; `phpstan-baseline.neon` −8 entradas obsoletas | commit `b5a860e`; wiring 346/0; phpunit 2034 OK; phpstan 0 errores |
| 2026-08-07 | **T31 — Fix G-04 Client (cascada Contact→User)**: `DeactivateContact`/`ReactivateContact`/`LinkUserToContact` publican `ContactDeactivated`/`ContactReactivated`/`ContactUserLinked` → `ContactStatusCascadeHandler` (REQ-CST-002) operativo | commit `d15eab3`; wiring 346/0; phpunit Client+CrossBc 355 OK; phpstan 0 errores |
| 2026-08-07 | **T01 — Spec de Notification BC aprobada**: `specs/006-notification/spec.md` actualizado (~60% → ~75% verificado). Estado real: Domain 100%, Application 100% (8 commands, 5 queries), Persistence 100%, Channels 50% (SMS/WhatsApp stubs), Cross-BC ~55% (107 eventos mapeados, 68 con destinatario, 53 con plantilla), 81 tests. Gaps documentados G-01..G-05 → tablero T33 | QA-Reviewer aprobó spec; wiring 346/0; phpunit Notification OK |
| 2026-08-07 | **T02 — Spec de Organization BC aprobada**: `specs/001-organization-spec/spec.md` actualizado (~67% → ~90% verificado, 1 corrección: G-09 archivos basura). 33 commands, 8 queries, 8 controllers, 3 reportes, dashboard completo. Gaps G-06..G-14 (G-06 crítico) → tablero T30/T34 | QA-Reviewer aprobó spec; 3 archivos basura `*-DESKTOP-GU1L412.php` verificados en filesystem |
| 2026-08-07 | **T03 — Spec de Client BC aprobada**: `specs/002-client/spec.md` + `tasks.md` actualizados (~70% → ~92% verificado, 1 corrección: G-11 falso negativo de wiring-check). 37 commands, 16 queries, 43 rutas API, **4 importers** (CONTEXT.md §2 corregido de 3), 2 reportes, 84 tests. Gaps G-04, G-06, G-10, G-11 → tablero T31/T35 | QA-Reviewer aprobó spec; 4 importers verificados en filesystem |
| 2026-08-06 | **Enforcement de máquina (spec 014)**: spec de gobernanza (DoR/DoD + gates), PHPStan nivel 5 + baseline (159), PHP-CS-Fixer (2020 archivos corregidos), `bin/wiring-check.php` (346 targets, 0 huérfanos), `bin/validate-project.php` (reglas Apéndice A automatizables + baseline), composer scripts (`test/stan/cs/wiring/validate/audit/check`), CI GitHub Actions (5 jobs + security audit). Fixes: strict_types en Kernel, `ListClientsQuery` final readonly, `SeedDevCommand` sin password hardcodeado (`ORBIS_DEV_SEED_PASSWORD`) | phpunit 2033 tests verdes, phpstan OK, validate exit 2 (6 warnings baseline) |
| 2026-08-06 | **Regla global vs repo**: AGENTS.md §Configuración global vs repo (agentes/skills globales en `~/.agents/`, prohibido duplicar en repo; excepciones `.codex/agents/*.toml` + `.ai/*.md`) + regla universal en `~/.agents/context/AGENTS.md`. Limpieza: `.claude/skills/` y `.opencode/agent/` sobrantes del repo eliminados | git, CHANGELOG_AI v1.2.0 |
| 2026-08-06 | **Hub global de agentes/skills (`~/.agents/`)**: fuente única multi-herramienta (opencode, Claude Code, Codex) con junctions (planner, qa-reviewer, developer, test-engineer, frontend, security-reviewer, docs + skills find-skills/frontend-design/caveman + context). `~/.agents/sync.ps1` idempotente. Wrappers `.opencode/agent/` y `.claude/skills/` del repo movidos a global; `.codex/agents/*.toml` generados por proyecto (cargar `.ai/{rol}.md`) | filesystem (junctions), git (deletions) |
| 2026-08-06 | **Consolidación configs de agentes**: `.ai/` como única fuente de prompts (README + planner.md + docs.md), 7 wrappers `.opencode/agent/*.md`, worktree huérfano `.claude/worktrees/` eliminado, token OpenRouter → `${OPENROUTER_API_KEY}` | filesystem (editado), CHANGELOG_AI v1.1.0 |
| 2026-08-06 | **T04/T05 — Remote GitHub configurado**: `https://github.com/RepoCMS/orbis.git`, rama `main` renombrada, push inicial + handoff operativo | git `4413faa`, HANDOFF.md §13 (actualizado) |
| 2026-08-06 | **Consolidación fuente de verdad**: TODOS.md deprecado → tablero `tasks/`; 7 agentes `.opencode` + CLAUDE.md con bootstrap AI-Native de 8 pasos; HANDOFF.md §13 actualizado | filesystem (editado) |
| 2026-08-06 | **T21 — docs core + standards completados**: `docs/architecture.md`, `docs/folder-structure.md`, `docs/dependencies.md`, `docs/coding-standards.md`, `docs/conventions.md`, `docs/glossary.md` | filesystem (verificado contra disco) |
| 2026-08-06 | **T22 — Sync AGENTS.md + PROJECT_CONTEXT.md**: bootstrap AI-Native de 8 pasos, regla ADR no negociable, `TASKS/`→`tasks/`, ORM 3.x, Alpine.js fuera | filesystem (editado) |
| 2026-08-06 | **T12/T13 — ADRs normalizados**: legacy 011-013 migrados a `ADR-009..011` (formato estándar), README actualizado con índice completo | git (deletions stageados) |
| 2026-08-06 | **.ai/ ×6 prompts** (architect, backend, frontend, reviewer, security, qa) + **docs/decisions/README.md** | filesystem (verificado) |
| 2026-08-06 | **ADR-001..008 creados** (DDD+Hexagonal, CQRS, ULID, DBAL types, Doctrine, Messenger, Shared Kernel, Soft Delete) | git `5b49841`, `26d84d0` |
| 2026-08-06 | **PROJECT_CONTEXT.md v1.0.0** creado (contexto primario AI-Native) | git `5b49841`, filesystem |
| 2026-08-06 | **docs/modules/ ×8** completados (client, expense, identity, inventory, location, notification, organization, support) | filesystem (verificado) |
| 2026-08-06 | **Tablero tasks/** creado (TODO.md, IN_PROGRESS.md, DONE.md) + ROADMAP.md + CHANGELOG_AI.md | este ciclo |
| 2026-08-05 | **Corrección AGENTS.md** — % de 5 BCs con discrepancia actualizados a valores reales | CONTEXT.md §10 #1 (✅ verificado 2026-08-05) |
| 2026-08-05 | **Corrección nombre** `ClientContractExpiringReportGenerator` → `ContractsExpiringReportGenerator` en TODOS.md | CONTEXT.md §10 #2, HANDOFF.md §12 #6 |
| 2026-08-05 | **MFA backup codes (#75)** — recovery route + rutas exentas en MfaSubscriber | CONTEXT.md §7, TODOS.md (✅ Resuelto; fecha exacta de resolución anterior — ⚠️ TODO: validar) |
| 2026-08-05 | **Seed permissions (#70)** — `identity.view_dashboard`, `client.report`, `inventory.report`, `support.report`, `organization.report`, `spare_part.*`, `ticket.assistance`, `ticket.approve`, `inventory.manage`, etc. | CONTEXT.md §7, docs/history.md (✅ Resuelto; fecha exacta ⚠️ TODO: validar) |
| 2026-08-05 | **CONTEXT.md** establecido como única fuente de verdad de estado | git `a7b59c6` |
| 2026-08-05 | **Split de AGENTS.md** (62.5 KB → ~15 KB): reglas vigentes + docs/analytics.md + docs/history.md | git `a59b778`, docs/history.md §3 |
| 2026-08-05 | **7 agent configs de opencode** (planner, qa-reviewer, developer, test-engineer, frontend, security-reviewer, docs) | git `d71b25f`, docs/history.md §3 |
| 2026-08-05 | **Limpieza de secrets** (`.env*` fuera de git, plantillas `.example`) + **scripts de validación** (validate_project.ps1, validate_reports.*, validate_importers.*) + node_modules fuera de tracking | git `a68eabc`, HANDOFF.md §12, docs/history.md §3 |
| 2026-07-31 | **Configuración opencode + CodeGraph MCP** (índice de conocimiento del código), API key expuesta removida | git `92fbc84` |
| 2026-07-30 | **Alineación specs↔código**: 6 specs con % corregidos; 9 orphan commands wireados (MonitorSla, EvaluateScheduledMaintenance, CheckOverdueCashAdvances, RevokePermissionFromRole, ActivateUser, ChangePermissionDescription, RegisterEmergencyPartUsage, CreateNotificationTemplate, SendNotification); conteo de tests corregido (1626 inflados → 452 reales); importers P2 (Usuarios, Modelos de Terminal, Órdenes de Compra); reportes P1 (Gastos por Período, Adelantos Pendientes, Personal Activo); dashboards con KPIs (Organization 4, Support 1, Inventory 2, Expense 1); 7 reportes P2/P3; Import Model Parts + Import Operating Expenses | docs/history.md §3 (git `0245a71`) |
| 2026-05-19 | **Support REST API**: 15 endpoints del ciclo de ticket + dashboard API (`ApiSupportDashboardController`) | git `ba15d8c`, CHANGELOG.md [Unreleased] |
| 2026-05-13 | **Initial commit** (template Specify) | git `6f1c10c` |

---

## Resueltos en sesiones previas (referencia anti-regresión, docs/history.md)

- `declare(strict_types=1)` en 100% de archivos PHP · enums lowercase · enums con custom DBAL types
- Scoping con ViewScopeResolver (stock, movimientos, spare parts)
- Integration tests cross-BC: EmployeeDeactivationCascadeTest (12 escenarios), ClientDeactivationCascadeTest (7 escenarios)
- Importers: Clientes, Terminales, Productos, Stock serializado/no serializado, Empleados, Usuarios, Órdenes de Compra, Gastos Operativos, Partes de Modelo

> ⚠️ **Nota de honestidad:** la fecha exacta de resolución de #75 y #70 no está registrada en las fuentes (TODOS.md/CONTEXT.md solo marcan "✅ Done"); la fecha citada es la de verificación. — [TODO: validar]

---

## Instrucciones de mantenimiento

1. **Cuando un agente cierra una tarea** de `tasks/TODO.md`: copia la fila aquí con la fecha real de cierre, verificación (commit/test) y elimínala del backlog.
2. La primera columna es la fecha **de cierre verificado**, no la de inicio.
3. No borrar entradas históricas — este archivo es evidencia. Si algo se revierte (regresión), agregar nota en la fila, no eliminarla.
4. Si una tarea se cierra con verificación incompleta, anotar `⚠️ TODO: validar` en la fila en vez de omitirla.
