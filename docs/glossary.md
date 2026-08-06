# Orbis — Glosario Técnico y de Negocio

> **Propósito:** Vocabulario común del dominio para que agentes IA y desarrolladores hablen el mismo idioma. Fuentes: `AGENTS.md`, `CONTEXT.md`, ADRs (001–008), `specs/004-inventory/spec.md`, `specs/003-support/spec.md`, `specs/001-organization-spec/spec.md`, `specs/007-expense/spec.md` y código real.
>
> **Última verificación:** 2026-08-06

---

## 1. Términos Técnicos / Arquitectónicos

| Término | Significado | BC/Área |
|---------|-------------|---------|
| **BC (Bounded Context)** | Límite explícito de dominio con lógica, persistencia y API propias. 13 BCs activos + Shared Kernel. Cross-BC solo por IDs (nunca instanciar entidades ajenas). | Global |
| **Shared Kernel** | Módulo `src/app/Shared/` con contratos compartidos entre BCs: VOs, interfaces cross-BC, eventos, excepciones, tipos DBAL e infraestructura de import/export. Solo interfaces y contratos (ADR-007). | `src/app/Shared/` |
| **ULID** | Identificador primario de entidades: 26 chars (Crockford base32), ordenable lexicográficamente, sin riesgo de colisión. `AbstractUlid` en Shared Kernel; custom DBAL type por ID (ADR-003). | Global |
| **VO (Value Object)** | Clase `final readonly` con sufijo `ValueObject` (regla 9), validación en constructor, inmutabilidad. Ej.: `UserIdValueObject`. | Domain |
| **Aggregate** | Raíz de consistencia (AR) con invariantes; constructor `private` + factory method con nombre de dominio (regla 3). Ej.: `Ticket` (506 líneas), `Movement`, `PurchaseOrder`. | Domain |
| **Soft delete** | Borrado lógico con columna `deleted_at`; método `softDelete()`; repositorios filtran `deleted_at IS NULL` (ADR-008). | Global |
| **Domain Event** | Evento de dominio interno (array + `pullDomainEvents`) disparado por handlers; cross-BC sale por Messenger async (ADR-006). | Domain / App |
| **CQRS** | Separación de Commands (escritura) y Queries (lectura) en la capa Application, cada uno con handler y DTO dedicados (ADR-002). | Application |
| **CQS** | Separación comando/consulta a nivel de uso: Commands por handler con repositorios; las Queries de lectura pueden usar métodos del repositorio directamente (Apéndice C, regla 6). | Application |
| **Hexagonal** | Puertos y adaptadores: Domain define interfaces (puertos); Infrastructure implementa adaptadores (Doctrine, mailers, APIs) (ADR-001). | Global |
| **DDD** | Domain-Driven Design: lógica de negocio en Domain puro, Application orquesta, Infrastructure persiste (ADR-001). | Global |
| **Wired** | Un Command/Query/Event con al menos un caller real (controller, console, subscriber, handler). Estado actual: 0 huérfanos (CONTEXT.md §1). Verificado con CodeGraph (`codegraph_callers`). | Global |
| **Huérfano (orphan)** | Clase sin ningún caller real. Prohibido por la regla de wiring (Apéndice A, regla 11). | Global |
| **Importer** | Proceso de carga masiva CSV/Excel, en `Shared/Infrastructure/Import/` + handlers por BC. 11 implementados (Clientes, Terminales, Modelos, Productos, Stock serializado, Stock no serializado, Empleados, Usuarios, Órdenes de Compra, Gastos Operativos, Partes de Modelo). | Global |
| **Report Generator** | Clase `{Nombre}ReportGenerator` que genera reportes (XLSX/PDF). 14 implementados. | Application/Report |
| **KPIs / Dashboard** | Métricas por BC expuestas como Query (ej. `IdentityDashboardMetrics`, `InventoryDashboardMetrics`, `SupportDashboardMetrics`) con UI Web + API. | Application / UI |
| **DBAL Type** | Custom Doctrine type por primitiva de dominio, `Infrastructure/Doctrine/Types/{Field}Type.php` (ADR-004). Enums `: string` persistidos DEBEN usar `AbstractStringEnumType`; prohibido `type="string"` en ORM. | Infrastructure |
| **ViewScopeResolver** | Application Service que resuelve el alcance de datos (scoping) por perfil del usuario (Apéndice D). | Application/Service |
| **Controller delgado** | Controller que solo valida input, delega a handlers y formatea respuesta; nunca SQL directo (regla 7, Apéndice C). | Presentation |
| **Response envelope** | Contrato de respuesta API: `{"data": ...}`, paginado `{"data":[], "total","page","pages"}`, error `{"error": "mensaje"}`. | API |

---

## 2. Términos de Negocio

| Término | Significado | BC/Área |
|---------|-------------|---------|
| **Serialized stock** | Stock con número de serie individual y único (serial único en el sistema, regla IN‑02). SerializedStock es entidad liviana (origin tracking eliminado). | Inventory |
| **Nonserialized stock** | Stock por cantidad/lotes (batches). No se puede `reserve()` un batch que no esté AVAILABLE. | Inventory |
| **Mochila (backpack)** | Ubicación tipo `backpack` auto-creada al crear un técnico (regla IN‑03). Los técnicos solo ven stock de su mochila (scoping). | Inventory / Location |
| **Transferencia** | Movimiento de stock entre ubicaciones (Movement). Ciclo: PENDING → IN_TRANSIT → COMPLETED/CANCELLED/REJECTED. Ownership: cancelar solo creador; confirmar/rechazar solo destinatario (IN‑05). | Inventory |
| **PENDING_REVIEW** | Estado de stock no serializado sospechoso que sale del cajero (IN‑09). También usado por CashAdvance (markPendingReview). | Inventory / Expense |
| **Tracking type** | `serialized` / `nonserialized` del producto. Fijo una vez que hay stock (IN‑01). | Inventory |
| **Técnico** | Empleado de campo con perfil técnico (`Employee.profile().isTechnician()` — descriptivo, no autorizativo). Autorización real por Role en Identity BC. | Organization / Support |
| **Cliente** | Entidad del BC Client; debe tener al menos 1 contacto activo (excepto cascada de desactivación). | Client |
| **Contrato** | Contrato por cliente en Client BC; base del SLA (`slaDeadlineAt` por cliente+contrato). Reportes: ContractsExpiring, TerminalsByClient. | Client |
| **Ticket lifecycle** | Ciclo de vida del ticket con 11 estados y máquina de estados completa; cancelable desde cualquier estado no-terminal (SU‑01). Estados: CREATED → ASSIGNED → IN_TRANSIT_OUT → WAITING → IN_ASSISTANCE → (PAUSED/RESUME) → IN_TRANSIT_BACK → PENDING_CLOSURE → CLOSED; → CANCELLED. | Support |
| **Finding** | Hallazgo/diagnóstico registrado en un ticket (RegisterFinding); los module findings siguen el árbol de módulos del modelo de ATM (spec 013). Endpoint separado de spare-parts (SU‑05). | Support |
| **Spare part** | Repuesto usado en tickets; endpoints separados de findings; scoping por `SparePartViewScopeResolver`. | Support / Inventory |
| **SLA** | `slaDeadlineAt` calculado al crear ticket (cliente+contrato). No bloquea el flujo; monitoreo de vencimiento/breach (SlaAboutToExpire, SlaBreached → Notification BC). | Support |
| **RMA** | Return Merchandise Authorization — registro de devoluciones (CreateRmaRecord). | Support |
| **QuickTemplate** | Plantilla rápida de checklist por modelo de ATM (CreateQuickTemplate/ApplyQuickTemplate). | Support |
| **Cash advance (adelanto)** | Adelanto de efectivo a técnico (CashAdvance): create, settle, markPendingReview, updateAmount, isOverdue, softDelete. Eventos: CashAdvanceCreated/Updated/Settled/PendingReview/PaymentPending. | Expense |
| **Gastos operativos** | Expense items con rechazo por item individual y evidence obligatoria para tipos específicos; items aprobados se mantienen al reenviar. | Expense |
| **STANDBY** | Período especial de trabajo en feriado/fin de semana (`EmployeeSpecialPeriodType` = standby). NO cambia `Employee.status`. | Organization |
| **ON_VACATION** | Status de empleado en vacaciones; se actualiza automáticamente por ciclo (OR‑03/OR‑07). | Organization |
| **EmployeeSpecialPeriod** | Períodos VACATION y STANDBY de un empleado. | Organization |
| **Conteo físico (PhysicalCount)** | Conteo de stock físico con aprobación; CRUD básico implementado, falta conciliación automática (gap G‑03). | Inventory |
| **Préstamo a técnico (TechnicianLoan)** | Préstamo de partes a técnicos con aprobación; UI parcial (gap G‑02). | Inventory |
| **Custodia (CustodyAssignment)** | Custodia de equipos; implementación inicial (gap G‑04). | Inventory |
| **Transformación** | Cambia código de producto y/o serial; solo productos `isTransformable=true`; da de baja parte A y crea parte B (IN‑07). | Inventory |
| **Reparación** | Mantiene el serial original (los bancos identifican seriales no originales); sin "reparar con nuevo serial" (IN‑06). | Inventory |
| **Multi-item request** | Solicitud de repuestos con estado por item (PENDING/FULFILLED/CANCELLED); fulfill parcial permitido (IN‑08). | Inventory |
| **Orden de compra (PO)** | PurchaseOrder con items y entradas (Entry); sugerencias de compra (GeneratePurchaseSuggestions). | Inventory |
| **MFA / Backup codes** | Autenticación multifactor en Identity (DisableUserMfa, GenerateBackupCodes, RevokeMfa). | Identity |
| **RefreshToken** | Token opaco con hash SHA-256 en BD, rotación en cada refresh, cleanup vía `identity:cleanup-refresh-tokens`. | Identity |
| **security_version** | Versión de seguridad del usuario: incrementa en deactivate, changePassword y assignRole; invalida sesión en refresh. | Identity |
| **Portal de cliente** | Acceso web para clientes (firewall `api_public`, security: false); los clientes solo ven sus tickets (scoping por `clientId`, SU‑09). | Support / Client |

---

## 3. Estado de verificación

- Conteos y porcentajes de BCs: ver `CONTEXT.md` §1/§2 (fuente única de verdad).
- ADRs referenciados (001–008): `docs/adr/` — ADR-009 a ADR-011 no existen aún (TODO: requiere validación si se esperaban).
- Specs citadas: `specs/004-inventory/spec.md` (Implementado ~95%), `specs/003-support/spec.md` (In Development ~85%), `specs/001-organization-spec/spec.md` (In Development ~67%), `specs/007-expense/spec.md` (Implementado 100%).
