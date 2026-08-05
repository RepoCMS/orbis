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
