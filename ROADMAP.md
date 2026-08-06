# ROADMAP.md — Hoja de Ruta del Proyecto Orbis

> **Propósito:** Visión de fases, estado real por BC y objetivos por horizonte de tiempo.
> **Fuente de verdad de estado:** `CONTEXT.md` (2026-08-05) — toda cifra de % proviene de allí.
> **Actualizado:** 2026-08-06
> **Último commit referenciado:** `4cc2c35` (CONTEXT.md) / `26d84d0` (ADR-004..008)

---

## 1. Fases del Proyecto

| Fase | Nombre | Período | Estado | Evidencia |
|------|--------|---------|--------|-----------|
| **0** | Fundación | 2026-05-13 → 2026-07-30 | ✅ Completada | `6f1c10c` (template Specify), `ba15d8c` (Support REST API), `0245a71` (alineación specs/código, wiring, importers, reportes) |
| **1** | Refactor de contexto | 2026-08-05 | ✅ Completada | `a7b59c6` (CONTEXT.md), `a59b778` (split AGENTS.md), `d71b25f` (roles opencode), `a68eabc` (env cleanup + validación) |
| **2** | **Transición AI-Native Repository** | 2026-08-06 → | 🔄 **ACTUAL** | `5b49841`, `26d84d0` (PROJECT_CONTEXT.md + ADR-001..008), docs/modules/×8, tablero `tasks/`, `.ai/` |
| **3** | Cerrar specs con discrepancia | próximos meses | ⬜ Pendiente | 5 BCs: Notification ~60%, Organization ~67%, Client ~70%, Identity ~85%, Support ~85% (CONTEXT.md §3) |
| **4** | Reportes / importers / deuda técnica | fase media | ⬜ Pendiente | Reportes P2/P3 restantes, Location tests, prefijos de tablas (ADR-013) |
| **5** | Seguridad e infraestructura | fase media/larga | ⬜ Pendiente | Decisión bcrypt vs argon2 (#42), remote GitHub, provider email real |

---

## 2. Estado Real por BC (copia literal de CONTEXT.md §2, 2026-08-05)

> ⚠️ Estos % son la realidad verificada el 2026-08-05. Los valores de AGENTS.md pre-refactor estaban inflados (hasta +35 puntos).

| BC | Spec Status (real) | Tests reales | Commands | Queries | Controllers | Reportes | Importers | Dashboard |
|----|--------------------|--------------|----------|---------|-------------|----------|-----------|-----------|
| **Identity** | ~85% | 77 | 32 | 13 | 15 | 1 | 1 | ✅ Web + API |
| **Client** | ~70% | 79 | 37 | 16 | 18 | 2 | 3 | ✅ Web + API |
| **Organization** | ~67% | 61 | 33 | 8 | 8 | 3 | 1 | ✅ Web + API |
| **Support** | ~85% | 93 | 35 | 20 | 12 | 2 | 0 | ✅ Web + API |
| **Inventory** | ~95% | 96 | 60 | 29 | 28 | 4 | 3 | ✅ API + Web |
| **Expense** | 100% | 30 | 15 | 8 | 5 | 2 | 1 | ✅ Web + API |
| **Location** | ~95% | 7 | — | — | 1 | — | — | ❌ API-only |
| **Notification** | ~60% | 8 | 8 | 5 | 8 | — | — | ✅ Web + API |
| **Cross-BC** | ~90% | — | — | — | — | — | — | — |
| **010a Employee-User** | ~95% | 3 | — | — | — | — | — | — |
| **010b SparePart Scope** | ~95% | 1 | — | — | — | — | — | — |
| **011 Notifications Stock** | 100% | — | — | — | — | — | — | — |
| **012 Multi-Item Request** | 100% | 75 | — | — | — | — | — | — |
| **013 ATM Module Tree** | 100% | 18 | — | — | — | — | — | ✅ Web |

**Totales verificados (CONTEXT.md §1):** 13 BCs + Shared Kernel · 1411 archivos PHP · 452 test files (reales) · 14 specs · **5 specs con discrepancia** · 14 reportes · 11 importers · 0 comandos huérfanos.

> ⚠️ **TODO: requiere validación** — Conteo de tests inconsistente entre fuentes: CONTEXT.md §1 dice **452 test files**; CHANGELOG.md [Unreleased] dice **384 test files / 1629 métodos**; docs/history.md menciona "1626 inflados → 452 reales". Verificar con conteo real (`tests/**`) antes de citar cifras en otro documento.

---

## 3. Hitos Completados

| Hito | Fecha | Fuente |
|------|-------|--------|
| MFA backup codes (#75) — recovery route + rutas exentas en MfaSubscriber | ✅ Resuelto (verificado 2026-08-05) | CONTEXT.md §7, TODOS.md |
| Seed permissions (#70) — `identity.view_dashboard`, `client.report`, etc. | ✅ Resuelto (verificado 2026-08-05) | CONTEXT.md §7, docs/history.md |
| Corrección AGENTS.md — % de 5 BCs con discrepancia | ✅ 2026-08-05 | CONTEXT.md §10 #1 |
| Corrección nombre `ClientContractExpiringReportGenerator` → `ContractsExpiringReportGenerator` | ✅ 2026-08-05 | CONTEXT.md §10 #2, HANDOFF.md §12 |
| CONTEXT.md como única fuente de verdad | ✅ 2026-08-05 | git `a7b59c6` |
| Split AGENTS.md (62.5 KB → ~15 KB) + docs/analytics.md + docs/history.md | ✅ 2026-08-05 | git `a59b778` |
| 7 agent configs opencode (`.opencode/agent/`) | ✅ 2026-08-05 | git `d71b25f` |
| Env cleanup (secrets fuera de git) + scripts de validación | ✅ 2026-08-05 | git `a68eabc` |
| ADR-001..008 (DDD+Hexagonal, CQRS, ULID, DBAL types, Doctrine, Messenger, Shared Kernel, Soft Delete) | ✅ 2026-08-06 | git `5b49841`, `26d84d0` |
| ADR-011 (TransactionManager), ADR-012 (Event Cascade), ADR-013 (Table Prefixes) en `docs/adr/` | ✅ Existen en el repo (⚠️ no indexados en docs/adr/README.md) | CHANGELOG.md [Unreleased] |
| 8 módulos documentados (`docs/modules/`: client, expense, identity, inventory, location, notification, organization, support) | ✅ Verificado 2026-08-06 | filesystem |
| Support REST API: 15 endpoints del ciclo de ticket + dashboard | ✅ 2026-05-19 | git `ba15d8c`, CHANGELOG.md |

---

## 4. Objetivos por Horizonte

### 4.1 Fase corta — próximo mes (transición AI-Native + cierre de specs)

| # | Objetivo | Estado | Fuente |
|---|----------|--------|--------|
| 1 | Completar infraestructura AI-Native: `docs/core/`, `docs/standards/`, `.ai/`, sync AGENTS.md/PROJECT_CONTEXT.md | 🔄 En curso (ver `tasks/IN_PROGRESS.md`) | plan de transformación 2026-08-06 |
| 2 | Configurar remote GitHub (bloquea handoff push/pull) | ⬜ Pendiente | CONTEXT.md §10 #3, HANDOFF.md §13 |
| 3 | Completar spec de **Notification BC** (~60% → objetivo 95%) | ⬜ Pendiente | CONTEXT.md §10 #4 |
| 4 | Completar spec de **Organization BC** (~67% → objetivo 95%) | ⬜ Pendiente | CONTEXT.md §10 #5 |
| 5 | Completar spec de **Client BC** (~70% → objetivo 95%) | ⬜ Pendiente | CONTEXT.md §10 #6 |
| 6 | Decidir bcrypt vs argon2 (#42) | ❓ Pendiente | CONTEXT.md §7, TODOS.md |

### 4.2 Fase media — 3 meses

| # | Objetivo | Estado | Fuente |
|---|----------|--------|--------|
| 1 | Identity Dashboard KPIs (6 KPIs según docs/analytics.md F.1.2) | ⬜ Pendiente | CONTEXT.md §10 #7, TODOS.md P1 |
| 2 | Tests para Location BC (hoy 7 tests, API-only, sin interfaz web) | ⬜ Pendiente | CONTEXT.md §2/§3, docs/history.md #3 |
| 3 | Migración de prefijos de tablas: Inventory BC (`stock_`/`serial_`/`inventory_` → `{bc}_`) | ⬜ Pendiente | CHANGELOG.md ADR-013, docs/history.md deuda #7 |
| 4 | Cerrar deuda técnica de naming (factory methods, controllers, domain events dual) | ⬜ Pendiente | docs/history.md §2 |
| 5 | Indexar ADR-011..013 en `docs/adr/README.md` + resolver ausencia de ADR-009/010 | ⬜ Pendiente | filesystem | 
| 6 | Verificar wiring de `SendNotification` (sin caller ni tests según docs/history.md) | ⚠️ Verificar | docs/history.md §1 #4 |

### 4.3 Fase larga — 6+ meses

| # | Objetivo | Estado | Fuente |
|---|----------|--------|--------|
| 1 | Provider de email real (forgot-password: hoy `LoggerForgotPasswordEmailSender` + `MAILER_DSN=null://null`) | ⬜ Pendiente | docs/history.md §1 #1 |
| 2 | Rate limiting en login (anti fuerza bruta) | ⬜ Pendiente | PROJECT_CONTEXT.md §7 |
| 3 | Password expiration notification | ⬜ Pendiente | PROJECT_CONTEXT.md §7 |
| 4 | Refresh token cleanup automatizado (cron) | ⬜ Pendiente | PROJECT_CONTEXT.md §7 |
| 5 | App móvil (API-first ya habilitada con JWT) | ⬜ Pendiente | AGENTS.md (visión de producto) |
| 6 | Roadmap formal de producto (features, fechas, recursos) | ⬜ No existe | — |

> **TODO: requiere validación** — Los horizontes 4.2/4.3 son estimaciones de prioridad basadas en las fuentes citadas; no existe un plan de producto formal con fechas. Los ítems sin fuente citada explícita no se incluyeron.

---

## 5. Blockers Activos (CONTEXT.md §7)

| # | Issue | Estado |
|---|-------|--------|
| #75 | MFA backup code verification en API | ✅ Resuelto |
| #70 | Seed missing permissions | ✅ Resuelto |
| #42 | bcrypt vs argon2 decisión | ❓ Pendiente |
| B1 | Nombre incorrecto `ClientContractExpiringReportGenerator` en TODOS.md | ✅ Corregido 2026-08-05 |
| B2–B5 | 5 specs con discrepancia vs AGENTS.md | ✅ AGENTS.md corregido 2026-08-05 (queda el trabajo de cerrar specs en Fase 3) |

---

## 6. Notas de Mantenimiento

- Este documento se actualiza cuando cambia el estado de una fase o se completa un objetivo (mover a la sección 3).
- Los % por BC **solo** se actualizan cuando el código real cambió y se verificó (conteo de tests, commands, queries). Nunca por estimación.
- Toda cifra conflictiva entre fuentes se marca con ⚠️ y `[TODO: validar]` — no se resuelve adivinando.
