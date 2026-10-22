# Orbis — Historial y Deuda Técnica

> **Origen:** extraído de AGENTS.md (2026-08-05) — historial de issues resueltos y deuda técnica pendiente. Referencia bajo demanda, no se carga en cada sesión.
> El detalle completo de las 102 inconsistencias resueltas vive en git history (AGENTS.md previo al refactor de 2026-08-05).

---

## 1. Issues Conocidos VIGENTES (2026-08-05)

| # | Issue | Estado |
|---|-------|--------|
| 1 | **Forgot-password email no enviado**: `LoggerForgotPasswordEmailSender` solo logea el token. `MAILER_DSN=null://null`. Se necesita provider real (Mailtrap/SMTP) o exponer token vía endpoint dev | ❌ Pendiente |
| 2 | **Client BC UI**: importers y dashboard KPIs pendientes (ver CONTEXT.md §2) | ❌ Parcial |
| 3 | **Location BC API-only**: revisar si requiere interfaz web | ❌ Pendiente |
| 4 | **SendNotification sin caller ni tests** (Notification BC) | ⚠️ Verificar |
| 5 | **Twig enum comparisons**: verificar templates con comparaciones de enum values (correcto: lowercase `in_transit`) | ⚠️ Auditoría |

## 2. Deuda Técnica Pendiente (del Apéndice B original)

| # | Problema | Dónde | Acción |
|---|----------|-------|--------|
| 4 | Factory methods inconsistentes | Mix de `create`, `register`, `hire`, `add` | Unificar en próxima refactorización |
| 5 | Controller naming inconsistente | `LocationController` vs `ApiIdentityUserController` | Unificar: `Api{BC}{Entity}Controller` |
| 6 | Patrón de Domain Events dual | Identity (externo) vs Support/Expense (interno) | Unificar: array interno + `pullDomainEvents()` |
| 7 | Doctrine table prefix inconsistente | Identity/Client sin prefijo | Agregar prefijo `identity_`, `client_` (baja prioridad) |

## 3. Historial de Correcciones (resumen)

**2026-08-05 — Refactor de contexto:**
- AGENTS.md compactado (62.5 KB → ~15 KB). Apéndice F → `docs/analytics.md`, historial → este archivo.
- CONTEXT.md establecido como única fuente de verdad de estado.
- CLAUDE.md raíz creado para que Claude Code lea la fuente de verdad.
- `.env*` con secrets fuera de git; plantillas `.example` creadas.
- node_modules (387 archivos, 20 MB) fuera de tracking.
- 7 agent configs de opencode creados en `.opencode/agent/`.

**2026-07-30 (última verificación previa):**
- 6 specs con % incorrectos corregidos (007, 010a, 010b, 011, 004, 012).
- 9 orphan commands wireados (MonitorSla, EvaluateScheduledMaintenance, CheckOverdueCashAdvances vía scheduler; RevokePermissionFromRole, ActivateUser, ChangePermissionDescription, RegisterEmergencyPartUsage, CreateNotificationTemplate, SendNotification vía controllers).
- Test count corregido (1626 inflados → 452 reales).
- Importers P2 completados: Usuarios, Modelos de Terminal, Órdenes de Compra.
- Reportes P1 completados: Gastos por Período, Adelantos Pendientes, Personal Activo.

**Resueltos en su totalidad (referencia para evitar regresiones):**
- strict_types en 100% de archivos PHP (verificado).
- Enums en lowercase en todos los BCs.
- Enums mapeados con custom DBAL types (PHP 8.4, ya no `type="string"`).
- Permisos seedeados: `identity.view_dashboard`, `auth_log.view`, `organization.view_dashboard`, `organization.import`, `inventory.report`, `support.report`, `expense.report`, `organization.report`, `spare_part.*`, `ticket.assistance`, `ticket.approve`, `inventory.manage`.
- Scoping de stock/movimientos/spare parts con ViewScopeResolver.
- Integration tests cross-BC: EmployeeDeactivationCascadeTest (12 scenarios), ClientDeactivationCascadeTest (7 scenarios).
- Dashboards: KPIs agregados en Organization (4), Support (1), Inventory (2), Expense (1).
- 7 reportes P2/P3 generados: vacaciones, horas extras, tickets por cliente, devolución renuncia, conteos, eventos seguridad, contratos por vencer.
- Import Model Parts (10 archivos) + Import Operating Expenses (5 archivos).

---

## 4. Discrepancias históricas (2026-08-07)

### 4.1 BCs vs AGENTS.md pre-refactor

| # | BC | AGENTS.md decía | Real | Impacto |
|---|----|-----------------|------|---------|
| 1 | **Client** | ~96% | ~92% (antes ~70%) | Spec `002-client` alineada 2026-08-07 (T03). Discrepancia residual de 4 puntos por gaps funcionales (G-04, G-10, G-11). |
| 2 | **Organization** | ~99% | ~90% (antes ~67%) | Spec `001-organization-spec` alineada 2026-08-07 (T02). Discrepancia residual de 9 puntos (G-06 crítico bloquea list API). |
| 3 | **Notification** | ~95% | ~75% (antes ~60%) | Spec `006-notification` alineada 2026-08-07 (T01). Cadena cross-BC ~55% funcional (68/107 con destinatario, 53/107 con plantilla). |
| 4 | **Identity** | ~97% | ~85% | AGENTS.md sobreestima en 12 puntos; dashboards y KPIs pendientes. |
| 5 | **Support** | ~98% | ~85% | AGENTS.md sobreestima en 13 puntos. |
| 6 | **Support tests** | 89 files | 93 files | AGENTS.md subestima. |
| 7 | **Client commands** | 34 (36 handlers) | 37 | AGENTS.md subestima. |

### 4.2 Specs vs código

| Spec | Status en spec.md | Estado real del código | Nota |
|------|-------------------|------------------------|------|
| `007-expense` | Implementado (100%) | Completo | Match |
| `010a-employee-user-invariant` | Implementado (~95%) | Completo (UI pending) | Match |
| `010b-sparepart-request-scope` | Implementado (~95%) | Completo | Match |
| `011-notifications-stock-requests` | Implementado (100%) | Completo | Match |
| `012-multi-item-request` | Implementado (100%) | Completo | Match |
| `013-atm-module-tree` | Implementado (100%) | Completo | Match |
| `001-identity-spec` | In Development (~85%) | Dashboard KPIs pendientes | AGENTS.md decía ~97% |
| `001-organization-spec` | Aprobado (~90%) | Reportes P2 + gaps G-06..G-14 | Spec alineada (T02) |
| `002-client` | Aprobado (~92%) | 43 rutas API, 4 importers, 84 tests; gaps G-04, G-10, G-11 | Spec alineada (T03) |
| `003-support` | In Development (~85%) | Scoping parcial | AGENTS.md decía ~98% |
| `004-inventory` | Implementado (~95%) | Casi completo | AGENTS.md decía ~96% |
| `005-location` | Completado ~95% | API-only, sin tests | AGENTS.md decía 100% |
| `006-notification` | Aprobado (~75%) | Cross-BC wiring ~55%; gaps G-01..G-05 | Spec alineada (T01) |
| `009-cross-bc` | Completado ~90% | Integration tests OK | AGENTS.md decía ~98% |

---

## 5. Inventario de reportes e importers (2026-08-07)

### 5.1 Reportes implementados

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

> El nombre real es `ContractsExpiringReportGenerator` (no `ClientContractExpiringReportGenerator`).

### 5.2 Importers implementados

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

## 6. Hitos de implementación (2026-08-12 a 2026-08-20)

- **T30 (G-06):** `ListEmployeesQuery` con paginación real + filtros (`serviceZoneId`/`page`/`limit`); desbloquea API list de Organization y cross-BC. Commit `b5a860e`.
- **T31 (G-04 Client):** cascada Contact→User operativa (handlers publican `ContactDeactivated`/`ContactReactivated`/`ContactUserLinked`). Commit `d15eab3`.
- **T32:** fix punto ciego de `bin/wiring-check.php` (stem-based matching; 22 huérfanos detectados).
- **T33 (G-03):** `RetryNotificationHandler` re-despacha `SendNotificationMessage` vía `MessageBusInterface`.
- **T34 (G-07/G-08/G-09):** `EmploymentPeriod::start()` en Create/Reactivate, `SpecialPeriodAutoProcessed` desde cron `org:process-special-periods`.
- **T35 (G-10/G-11):** bug SQL `contacts`→`client_contacts` corregido; `ListContactsByClientQuery` reparada.
- **T36:** fix `UserContactResolver` tests, `AbstractUlid::equals()`, baseline PHPStan 159 → 142. CI verde: PHPUnit 2082, PHPStan 0, CS 0, Wiring 0, Validate exit 2. Commit `bbd052f`.

---

## 7. Consolidación de contexto IA (2026-10-22)

Se eliminaron puntos de entrada duplicados para reducir ~75k tokens de documentación potencial y dejar una sola fuente por tema:

- `PROJECT_CONTEXT.md` → contenido vigente fusionado en `docs/architecture.md` (arquitectura/setup) y `CONTEXT.md` (estado). Estaba desactualizado (ORM 2.x, Alpine.js).
- `HANDOFF.md` → reemplazado por `docs/ai-protocol.md` + `CONTEXT.md` §0.
- `.ai/` → eliminado; los agentes globales (`~/.agents/agents/`) usan `AGENTS.md` + `docs/ai-protocol.md`.
- `.codex/agents/` → eliminado (solo se usa opencode).
- Spec Kit (`.specify/`, `.github/agents|prompts/speckit.*`, `constitution.md`, `.github/copilot-instructions.md`) → eliminado; el flujo real usa `specs/` + `docs/ai-protocol.md`.
- `CLAUDE.md` y `.claude/` → eliminados (solo se usa opencode).
- `TODOS.md`, `validate_reports.*`, `validate_importers.*` → eliminados (obsoletos).
- Caches locales (`.phpunit.cache`, `.php-cs-fixer.cache`, `var/cache`) → eliminados.
