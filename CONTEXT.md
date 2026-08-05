# Orbis — Contexto Maestro (fuente única de verdad)

> **Propósito:** Este documento es la referencia que toda IA, agente o desarrollador debe consultar antes de actuar. Contiene el estado REAL del proyecto, no el optimista de AGENTS.md.
>
> **Actualizado:** 2026-08-05
> **Rama activa:** `001-identity-spec`
> **Último commit:** `4cc2c35` — Checkpoint antes de estructurar

---

## 1. Resumen Ejecutivo

| Métrica | Valor |
|---------|-------|
| BCs activos | 13 + Shared Kernel |
| Total archivos PHP | 1411 (CodeGraph) |
| Total test files | 452 (reales, no inflados) |
| Specs existentes | 14 |
| Specs con discrepancia vs AGENTS.md | **5** (ver §3) |
| Reportes implementados | 14 |
| Importers implementados | 11 |
| Comandos huérfagos conocidos | 0 (todos wireados) |

---

## 2. Estado Real por Bounded Context

### Tabla maestra

| BC | Spec Status (real) | AGENTS.md dice | Tests reales | Commands (reales) | Queries (reales) | Controllers (reales) | Reportes | Importers | Dashboard |
|----|--------------------|----------------|-------------|-------------------|------------------|---------------------|----------|-----------|-----------|
| **Identity** | ~85% | ~97% | 77 | 32 | 13 | 15 | 1 (SecurityEvents) | 1 (Import Users) | ✅ Web + API |
| **Client** | ~70% | ~96% | 79 | 37 | 16 | 18 | 2 (ContractsExpiring, TerminalsByClient) | 3 (Clientes, Terminales, Modelos) | ✅ Web + API |
| **Organization** | ~67% | ~99% | 61 | 33 | 8 | 8 | 3 (ActivePersonnel, Overtime, ScheduledVacations) | 1 (Import Employees) | ✅ Web + API |
| **Support** | ~85% | ~98% | 93 | 35 | 20 | 12 | 2 (Technician, TicketsByClient) | 0 | ✅ Web + API |
| **Inventory** | ~95% | ~96% | 96 | 60 | 29 | 28 | 4 (ValuedInventory, Movement, ResignationReturn, PhysicalCount) | 3 (Productos, Stock serializado, Stock no serializado) | ✅ API + Web |
| **Expense** | 100% | 100% | 30 | 15 | 8 | 5 | 2 (Expense, Advance) | 1 (Import Operating Expenses) | ✅ Web + API |
| **Location** | ~95% | 100% | 7 | — | — | 1 | — | — | ❌ API-only |
| **Notification** | ~60% | ~95% | 8 | 8 | 5 | 8 | — | — | ✅ Web + API |
| **Cross-BC** | ~90% | ~98% | — | — | — | — | — | — | — |
| **010a Employee-User** | ~95% | ~98% | 3 | — | — | — | — | — | — |
| **010b SparePart Scope** | ~95% | ~98% | 1 | — | — | — | — | — | — |
| **011 Notifications Stock** | 100% | 100% | — | — | — | — | — | — | — |
| **012 Multi-Item Request** | 100% | 100% | 75 | — | — | — | — | — | — |
| **013 ATM Module Tree** | 100% | 100% | 18 | — | — | — | — | — | ✅ Web |

### Discrepancias críticas (AGENTS.md vs realidad)

| # | BC | AGENTS.md dice | Real | Impacto |
|---|----|----------------|------|---------|
| 1 | **Client** | ~96% | ~70% | AGENTS.md sobreestima en 26 puntos. Una IA que lea AGENTS.md cree que Client está casi listo cuando aún tiene gaps importantes. |
| 2 | **Organization** | ~99% | ~67% | AGENTS.md sobreestima en 32 puntos. Organization tiene mucho código pero specs incompletos. |
| 3 | **Notification** | ~95% | ~60% | AGENTS.md sobreestima en 35 puntos. Notification BC tiene código pero la spec no refleja el progreso real. |
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
| `001-organization-spec` | In Development (~67%) | Reportes P2 pendientes | AGENTS.md dice ~99% — gran discrepancia |
| `002-client` | In Development (~70%) | Importers P2/P3 pendientes | AGENTS.md dice ~96% — gran discrepancia |
| `003-support` | In Development (~85%) | Scoping parcial | AGENTS.md dice ~98% — discrepancia |
| `004-inventory` | Implementado (~95%) | Casi completo | AGENTS.md dice ~96% — aceptable |
| `005-location` | Completado ~95% | API-only, sin tests | AGENTS.md dice 100% — discrepancia |
| `006-notification` | In Development (~60%) | Cross-BC wiring pendiente | AGENTS.md dice ~95% — gran discrepancia |
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
   → Actualiza TODOS.md con estado real
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
3. **Siempre actualizar TODOS.md** con el estado real después de cada checkpoint.
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
cat TODOS.md
```

---

## 7. Blockers Activos

| # | Issue | Impacto | Estado |
|---|-------|---------|--------|
| #75 | MFA backup code verification en API | Security feature | ✅ Resuelto |
| #70 | Seed missing permissions | Dashboards/reports inaccesibles | ✅ Resuelto |
| #42 | bcrypt vs argon2 decisión | Password hashing | ❓ Pendiente |
| B1 | `ClientContractExpiringReportGenerator` nombre incorrecto en TODOS.md | Confusión de agentes | ⚠️ Nombrar correctamente |
| B2 | 5 specs con discrepancia vs AGENTS.md | IAs leen status incorrecto | ⚠️ Corregir AGENTS.md |
| B3 | Notification BC ~60% pero AGENTS.md dice ~95% | IAs sobreestiman progreso | ⚠️ Actualizar AGENTS.md |
| B4 | Organization BC ~67% pero AGENTS.md dice ~99% | IAs sobreestiman progreso | ⚠️ Actualizar AGENTS.md |
| B5 | Client BC ~70% pero AGENTS.md dice ~96% | IAs sobreestiman progreso | ⚠️ Actualizar AGENTS.md |

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

1. **[URGENTE]** Corregir AGENTS.md — actualizar porcentajes de 5 BCs con discrepancia.
2. **[URGENTE]** Corregir nombre `ClientContractExpiringReportGenerator` → `ContractsExpiringReportGenerator` en TODOS.md.
3. **[ALTA]** Completar specs de Notification BC (~60% → objetivo 95%).
4. **[ALTA]** Completar specs de Organization BC (~67% → objetivo 95%).
5. **[ALTA]** Completar specs de Client BC (~70% → objetivo 95%).
6. **[MEDIA]** Implementar Identity Dashboard KPIs (pendientes según TODOS.md).
7. **[MEDIA]** Crear `HANDOFF.md` con historial de cambios entre agentes.
8. **[BAJA]** Actualizar `.opencode/opencode.jsonc` con roles de agentes si se expande.
