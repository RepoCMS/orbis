# tasks/DONE.md — Trabajo Completado

> **Actualizado:** 2026-08-06
> Tabla cronológica de tareas/hitos cerrados. Fuente = evidencia (git commit, CONTEXT.md, docs/history.md, CHANGELOG.md).

---

## Completados

| Fecha | Tarea | Fuente |
|-------|-------|--------|
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
