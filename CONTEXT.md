# Orbis — Contexto Maestro (fuente única de verdad)

> **Propósito:** Este documento es la referencia que toda IA, agente o desarrollador debe consultar antes de actuar. Contiene el estado REAL del proyecto. AGENTS.md (reglas de código) ya no publica % de BCs — esta es la única tabla de estado.
>
> **Actualizado:** 2026-08-07
> **Rama activa:** `main` (remote: `https://github.com/RepoCMS/orbis.git`)
> **Último commit:** `7d8e4fa` — ciclo T01-T03: specs Notification/Organization/Client realineadas y aprobadas (2026-08-07), docs + CHANGELOG_AI v1.4.0 (ver §2/§3)

---

## 1. Resumen Ejecutivo

| Métrica | Valor |
|---------|-------|
| BCs activos | 13 + Shared Kernel |
| Total archivos PHP | 1411 (CodeGraph) |
| Total test files | 452 (reales, no inflados) |
| Specs existentes | 15 (incl. 014-governance) |
| Specs con discrepancia vs AGENTS.md | **5** (ver §3) |
| Reportes implementados | 14 |
| Importers implementados | 11 |
| Comandos huérfagos conocidos | 0 (todos wireados — verificado por CI `wiring`) |
| Tests verdes (baseline 2026-08-06) | 2033 tests / 5047 assertions |
| PHPStan nivel 5 (baseline) | 159 errores preexistentes permitidos; nuevos bloquean CI |
| PHP-CS-Fixer | 2020/2360 archivos normalizados (commit del ciclo) |
| Gates CI | 5 jobs en `.github/workflows/ci.yml` (test, stan, cs, wiring, validate) + security (audit) |
| Contrato de calidad | `specs/014-governance/spec.md` (DoR §2, DoD §3, gates §5) |

---

## 2. Estado Real por Bounded Context

### Tabla maestra

| BC | Spec Status (real) | AGENTS.md decía (pre-refactor 2026-08-05) | Tests reales | Commands (reales) | Queries (reales) | Controllers (reales) | Reportes | Importers | Dashboard |
|----|--------------------|----------------|-------------|-------------------|------------------|---------------------|----------|-----------|-----------|
| **Identity** | ~85% | ~97% | 77 | 32 | 13 | 15 | 1 (SecurityEvents) | 1 (Import Users) | ✅ Web + API |
| **Client** | ~92% | ~96% | 84 | 37 | 16 | 18 | 2 (ContractsExpiring, TerminalsByClient) | 4 (Clientes, Terminales, Modelos, Partes de Modelo) | ✅ Web + API |
| **Organization** | ~90% | ~99% | 61 | 33 | 8 | 8 | 3 (ActivePersonnel, Overtime, ScheduledVacations) | 1 (Import Employees) | ✅ Web + API |
| **Support** | ~85% | ~98% | 93 | 35 | 20 | 12 | 2 (Technician, TicketsByClient) | 0 | ✅ Web + API |
| **Inventory** | ~95% | ~96% | 96 | 60 | 29 | 28 | 4 (ValuedInventory, Movement, ResignationReturn, PhysicalCount) | 3 (Productos, Stock serializado, Stock no serializado) | ✅ API + Web |
| **Expense** | 100% | 100% | 30 | 15 | 8 | 5 | 2 (Expense, Advance) | 1 (Import Operating Expenses) | ✅ Web + API |
| **Location** | ~95% | 100% | 7 | — | — | 1 | — | — | ❌ API-only |
| **Notification** | ~75% | ~95% | 81 | 8 | 5 | 8 | — | — | ✅ Web + API |
| **Cross-BC** | ~90% | ~98% | — | — | — | — | — | — | — |
| **010a Employee-User** | ~95% | ~98% | 3 | — | — | — | — | — | — |
| **010b SparePart Scope** | ~95% | ~98% | 1 | — | — | — | — | — | — |
| **011 Notifications Stock** | 100% | 100% | — | — | — | — | — | — | — |
| **012 Multi-Item Request** | 100% | 100% | 75 | — | — | — | — | — | — |
| **013 ATM Module Tree** | 100% | 100% | 18 | — | — | — | — | — | ✅ Web |

> **Nota de verificación (2026-08-07):** % de Client, Organization y Notification actualizados tras el ciclo de alineación de specs T01-T03 (specs `002-client`, `001-organization-spec`, `006-notification` aprobados por QA-Reviewer). Verificado con CodeGraph + phpunit (696 tests de los 3 BCs OK) + wiring-check (346 targets, 0 huérfanos).

### Discrepancias históricas (AGENTS.md pre-refactor vs realidad)

| # | BC | AGENTS.md dice | Real | Impacto |
|---|----|----------------|------|---------|
| 1 | **Client** | ~96% | ~92% (antes ~70%) | ✅ Spec `002-client` alineada 2026-08-07 (T03). Discrepancia residual de 4 puntos por gaps funcionales (G-04, G-10, G-11). |
| 2 | **Organization** | ~99% | ~90% (antes ~67%) | ✅ Spec `001-organization-spec` alineada 2026-08-07 (T02). Discrepancia residual de 9 puntos (G-06 crítico bloquea list API). |
| 3 | **Notification** | ~95% | ~75% (antes ~60%) | ✅ Spec `006-notification` alineada 2026-08-07 (T01). Discrepancia residual de 20 puntos: cadena cross-BC ~55% funcional (68/107 con destinatario, 53/107 con plantilla). |
| 4 | **Identity** | ~97% | ~85% | AGENTS.md sobreestima en 12 puntos. Identity tiene dashboards pendientes y algunos KPIs sin implementar. |
| 5 | **Support** | ~98% | ~85% | AGENTS.md sobreestima en 13 puntos. |
| 6 | **Support tests** | 89 files | 93 files | Menor, pero AGENTS.md subestima. |
| 7 | **Client commands** | 34 (36 handlers) | 37 | AGENTS.md subestima. |

---

## 3. Discrepancias de Specs vs Código

| Spec | Status en spec.md | Estado real del código | Nota |
|------|-------------------|------------------------|------|
| `007-expense` | Implementado (100%) | ✅ Completo | Match |
| `010a-employee-user-invariant` | Implementado (~95%) | ✅ Completo (UI pending) | Match |
| `010b-sparepart-request-scope` | Implementado (~95%) | ✅ Completo | Match |
| `011-notifications-stock-requests` | Implementado (100%) | ✅ Completo | Match |
| `012-multi-item-request` | Implemented (100%) | ✅ Completo | Match |
| `013-atm-module-tree` | Implementado — 100% | ✅ Completo | Match |
| `001-identity-spec` | In Development (~85%) | Dashboard KPIs pendientes | AGENTS.md dice ~97% — discrepancia |
| `001-organization-spec` | Aprobado (~90% — verificado 2026-08-07) | Reportes P2 + gaps G-06..G-14 (G-06 crítico: parámetros fantasma en ListEmployeesQuery) | ✅ Spec alineada (T02) — implementar gaps |
| `002-client` | Aprobado (~92% — verificado 2026-08-07) | 43 rutas API, 4 importers, 84 tests; gaps G-04 (cascada Contact→User), G-10, G-11 | ✅ Spec alineada (T03) — implementar gaps |
| `003-support` | In Development (~85%) | Scoping parcial | AGENTS.md dice ~98% — discrepancia |
| `004-inventory` | Implementado (~95%) | Casi completo | AGENTS.md dice ~96% — aceptable |
| `005-location` | Completado ~95% | API-only, sin tests | AGENTS.md dice 100% — discrepancia |
| `006-notification` | Aprobado (~75% — verificado 2026-08-07) | Cross-BC wiring ~55% (107 eventos mapeados, 68 con destinatario, 53 con plantilla); gaps G-01..G-05 (G-03: retry no re-despacha) | ✅ Spec alineada (T01) — implementar gaps |
| `009-cross-bc` | Completado ~90% | Integration tests OK | AGENTS.md dice ~98% — discrepancia |

---

## 4. Reportes Implementados (verificados con CodeGraph)

| Reporte | BC | Archivo | Status |
|---------|----|---------|--------|
| ContractsExpiring | Client | `Client/Application/Report/ContractsExpiring/ContractsExpiringReportGenerator.php` | ✅ |
| TerminalsByClient | Client | `Client/Application/Report/TerminalsByClient/TerminalsByClientReportGenerator.php` | ✅ |
| ActivePersonnel | Organization | `Organization/Application/Report/ActivePersonnel/ActivePersonnelReportGenerator.php` | ✅ |
| Overtime | Organization | `Organization/Application/Report/Overtime/OvertimeReportGenerator.php` | ✅ |
| ScheduledVacations | Organization | `Organization/Application/Report/ScheduledVacations/ScheduledVacationsReportGenerator.php` | ✅ |
| Expense | Expense | `Expense/Application/Report/Expense/ExpenseReportGenerator.php` | ✅ |
| Advance | Expense | `Expense/Application/Report/Advance/AdvanceReportGenerator.php` | ✅ |
| ValuedInventory | Inventory | `Inventory/Application/Report/ValuedInventory/ValuedInventoryReportGenerator.php` | ✅ |
| Movement | Inventory | `Inventory/Application/Report/Movement/MovementReportGenerator.php` | ✅ |
| ResignationReturn | Inventory | `Inventory/Application/Report/ResignationReturn/ResignationReturnReportGenerator.php` | ✅ |
| PhysicalCount | Inventory | `Inventory/Application/Report/PhysicalCount/PhysicalCountReportGenerator.php` | ✅ |
| Technician | Support | `Support/Application/Report/Technician/TechnicianReportGenerator.php` | ✅ |
| TicketsByClient | Support | `Support/Application/Report/TicketsByClient/TicketsByClientReportGenerator.php` | ✅ |
| SecurityEvents | Identity | `Identity/Application/Report/SecurityEvents/SecurityEventsReportGenerator.php` | ✅ |

### ⚠️ Nombre incorrecto en TODOS.md
TODOS.md referencia `ClientContractExpiringReportGenerator` pero el nombre real es `ContractsExpiringReportGenerator`.

---

## 5. Importers Implementados (verificados)

| Importer | BC | Archivos | Status |
|----------|----|---------|--------|
| Import Clientes | Client | 10+ archivos | ✅ |
| Import Terminales | Client | ✅ | ✅ |
| Import Modelos de Terminal | Client | ✅ | ✅ |
| Import Productos | Inventory | ✅ | ✅ |
| Import Stock serializado | Inventory | ✅ | ✅ |
| Import Stock no serializado | Inventory | ✅ | ✅ |
| Import Empleados | Organization | ✅ | ✅ |
| Import Usuarios | Identity | ✅ | ✅ |
| Import Órdenes de Compra | Inventory | ✅ | ✅ |
| Import Gastos Operativos | Expense | ✅ | ✅ |
| Import Partes de Modelo | Client | 10+ archivos | ✅ |

---

## 6. Handoff Protocol (actualizado)

### Flujo entre agentes

```
1. PLANNER
   → Lee CONTEXT.md + specs/{num}-{bc}/spec.md
   → Escribe/actualiza spec.md con sección "Wiring"
   → Actualiza `tasks/` (TODO/IN_PROGRESS/DONE) con estado real
   ↓
2. QA-REVIEWER
   → Valida spec con checklist DDD (Apéndice A de AGENTS.md)
   → Verifica wiring con CodeGraph (codegraph_callers)
   → Confirma que cada Command/Query tiene al menos un caller real
   ↓
3. DEVELOPER
   → Implementa Domain → Application → Infrastructure → Presentation
   → Corre tests: php vendor/bin/phpunit
   → Verifica wiring con CodeGraph
   ↓
4. HANDOFF CHECKPOINT
   → git add -A + commit descriptivo
   → Actualiza CONTEXT.md si cambió status de algún BC
   → Push a rama
```

### Reglas no negociables del handoff

1. **Nunca avanzar sin verificar wiring** — `codegraph_callers` de cada Command/Query nuevo.
2. **Nunca confiar en AGENTS.md como fuente de verdad** — usar CONTEXT.md como fuente primaria.
3. **Siempre actualizar `tasks/`** con el estado real después de cada checkpoint.
4. **Nunca modificar dos BCs sin involucrar al Planner primero.**
5. **Tests son obligatorios** — ningún Command/Query se entrega sin test.

### Checkpoints de herramienta

Cuando se alterna entre Claude Code y opencode:

```bash
# ANTES de cambiar herramienta:
git add -A
git commit -m "WIP: checkpoint - $(date +%H:%M)"
git push origin 001-identity-spec

# DESPUÉS de regresar:
git pull origin 001-identity-spec
cat CONTEXT.md
cat tasks/IN_PROGRESS.md
```

---

## 7. Blockers Activos

| # | Issue | Impacto | Estado |
|---|-------|---------|--------|
| #75 | MFA backup code verification en API | Security feature | ✅ Resuelto |
| #70 | Seed missing permissions | Dashboards/reports inaccesibles | ✅ Resuelto |
| #42 | bcrypt vs argon2 decisión | Password hashing | ❓ Pendiente |
| B1 | `ClientContractExpiringReportGenerator` nombre incorrecto en TODOS.md | Confusión de agentes | ⚠️ Nombrar correctamente |
| B2 | 5 specs con discrepancia vs AGENTS.md | IAs leen status incorrecto | ✅ Specs de Client/Organization/Notification alineadas 2026-08-07 (T01-T03); restan Identity y Support |
| B3 | Notification BC ~60% pero AGENTS.md dice ~95% | IAs sobreestiman progreso | ✅ Spec `006-notification` aprobada (~75%) 2026-08-07 (T01) |
| B4 | Organization BC ~67% pero AGENTS.md dice ~99% | IAs sobreestiman progreso | ✅ Spec `001-organization-spec` aprobada (~90%) 2026-08-07 (T02) |
| B5 | Client BC ~70% pero AGENTS.md dice ~96% | IAs sobreestiman progreso | ✅ Spec `002-client` aprobada (~92%) 2026-08-07 (T03) |
| B6 | **G-06 (crítico)**: `ListEmployeesQuery` con parámetros fantasma → `Error: Unknown named parameter` en runtime | Bloquea API list de Organization + cross-BC (Custody, Expense, Support) | ✅ Resuelto — T30 (commit `b5a860e`, 2026-08-07) |
| B7 | **G-04 Client**: cascada Contact→User rota (eventos nunca publicados) | Desactivación de cliente no propaga a User | ✅ Resuelto — T31 (commit `d15eab3`, 2026-08-07) |
| B8 | **Punto ciego `bin/wiring-check.php`**: no detecta Queries huérfanas cuyo handler pareja importe su FQCN (verificado con `ListContactsByClientQuery`) | Gate de wiring da falsos negativos (regla 11) | ⚠️ Deuda — tarea T32 |

---

## 8. Convenciones de Nomenclatura (resumen)

| Elemento | Patrón | Ejemplo |
|----------|--------|---------|
| Command | `{Action}{Resource}Command` | `CreateUserCommand` |
| Handler | `{Action}{Resource}Handler` | `CreateUserHandler` |
| Query | `{Find|List}{Resource}Query` | `ListUsersQuery` |
| QueryHandler | `{Find|List}{Resource}Handler` | `ListUsersHandler` |
| Response DTO | `{Resource}Response` | `UserResponse` |
| Controller (API) | `Api{BC}{Entity}Controller` | `ApiIdentityUserController` |
| Controller (Web) | `{BC}{Entity}Controller` | `IdentityDashboardController` |
| Report Generator | `{Nombre}ReportGenerator` | `ContractsExpiringReportGenerator` |
| Importer | `Import{Resource}Handler` | `ImportClientHandler` |
| DBAL Type | `{FieldName}Type` | `UserTokenTypeType` |
| Enum | `{Name}Enum` o `{Name}Status` | `UserStatus`, `TicketStatus` |
| ValueObject | `{Name}ValueObject` | `UserIdValueObject` |
| ViewScopeResolver | `{Resource}ViewScopeResolver` | `StockViewScopeResolver` |

---

## 9. Estructura de Directorios por BC

```
src/app/{BC}/
├── Application/
│   ├── Command/{Action}{Resource}/
│   │   ├── {Action}{Resource}Command.php
│   │   └── {Action}{Resource}Handler.php
│   ├── Query/{Find|List}{Resource}/
│   │   ├── {Find|List}{Resource}Query.php
│   │   └── {Find|List}{Resource}Handler.php
│   ├── Query/Response/{Resource}Response.php
│   ├── Service/{Resource}ViewScopeResolver.php  (si aplica)
│   └── Report/{Nombre}ReportGenerator.php       (si aplica)
├── Domain/
│   ├── Entities/{Entity}.php
│   ├── ValueObjects/{Name}ValueObject.php
│   ├── Enums/{Name}Enum.php
│   ├── Repositories/{Resource}RepositoryInterface.php
│   ├── Events/{EventName}.php
│   └── Exceptions/{BC}DomainException.php
├── Infrastructure/
│   ├── Persistence/Doctrine/
│   │   ├── Doctrine{Resource}Repository.php
│   │   └── Mapping/{Entity}.orm.xml
│   ├── Doctrine/Types/{Field}Type.php
│   └── Service/{IntegrationService}.php
└── Presentation/
    ├── Http/
    │   ├── Api{BC}{Entity}Controller.php
    │   └── {BC}{Entity}Controller.php (web)
    └── Console/
        └── {CommandName}Command.php
```

---

## 10. Próximos Pasos Recomendados

1. **[URGENTE]** Corregir AGENTS.md — actualizar porcentajes de 5 BCs con discrepancia. ✅ Verificado 2026-08-05
2. **[URGENTE]** Corregir nombre `ClientContractExpiringReportGenerator` → `ContractsExpiringReportGenerator` en TODOS.md. ✅ Corregido 2026-08-05
3. **[ALTA]** Configurar remote GitHub (sin él, el handoff push/pull no funciona). Ver HANDOFF.md §13. ✅ Hecho 2026-08-06
4. **[ALTA]** Completar specs de Notification BC (~60% → objetivo 95%). ✅ Spec `006-notification` aprobada (~75%) 2026-08-07 — implementar gaps G-01..G-05 (ver §7 B6-B8).
5. **[ALTA]** Completar specs de Organization BC (~67% → objetivo 95%). ✅ Spec `001-organization-spec` aprobada (~90%) 2026-08-07 — implementar gaps G-06..G-14 (G-06 crítico, ver §7 B6).
6. **[ALTA]** Completar specs de Client BC (~70% → objetivo 95%). ✅ Spec `002-client` aprobada (~92%) 2026-08-07 — implementar gaps G-06, G-10, G-11 (G-04 ✅ T31, ver §7 B7).
7. ✅ **G-06** (crítico) implementado — T30 (commit `b5a860e`): `ListEmployeesQuery` con paginación real + filtros (`serviceZoneId`/`page`/`limit`); API list de Organization y cross-BC desbloqueadas.
8. ✅ **G-04 Client** implementado — T31 (commit `d15eab3`): cascada Contact→User operativa (handlers publican `ContactDeactivated`/`ContactReactivated`/`ContactUserLinked`).
9. **[ALTA]** Corregir **punto ciego de `bin/wiring-check.php`** (queries huérfanas con handler pareja que importe su FQCN). → tarea T32
10. **[MEDIA]** Implementar Identity Dashboard KPIs (pendientes según TODOS.md).
11. **[BAJA]** Actualizar `.opencode/opencode.jsonc` con roles de agentes si se expande. ✅ Hecho 2026-08-05: `.opencode/agent/{planner,qa-reviewer,developer,test-engineer,frontend,security-reviewer,docs}.md`
