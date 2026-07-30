# Tasks — Support (Ticketing & Field Service)

**Spec**: `specs/003-support/spec.md`
**Código**: `src/app/Support/`
**Tests**: 43 test files (Unit) — 284 tests, 691 assertions
**Estimado**: ~85% implementado

---

## Leyenda
- ✅ Completado
- ❌ Pendiente

---

## TASK-01: Domain Layer

### 1.1 Entities (18)
- [x] `Ticket` — Aggregate root (506 lines, state machine 11 estados)
- [x] `TicketAssignment`, `TicketFinding`, `TicketEvidence`, `TicketModuleFinding`
- [x] `FindingEvidence`, `FindingComponent`
- [x] `Journey`, `TicketPause`, `TicketReschedule`, `TicketWaiting`
- [x] `TicketSparePart`, `TicketPendingSparePart`
- [x] `TicketAuditLog`
- [x] `QuickTemplate`, `QuickTemplateItem`
- [x] `RmaRecord`, `ScheduledMaintenance`

### 1.2 AtmModel Subdomain
- [x] `AtmModel` — enum de modelos de ATM
- [x] `AtmModuleNode` — VO de nodo del árbol
- [x] `AtmModuleTreeData` — definiciones estáticas del árbol
- [x] `AtmModuleTreeRegistry` — domain service que combina módulos comunes + específicos
- [x] `ModulePathValueObject` — valida path jerárquico
- [x] `FindingType` — ok / faulty / replaced / pending
- [x] `EvidenceMoment` — before / after

### 1.3 Value Objects (18)
- [x] Todos los ULID VOs: `TicketId`, `TicketAssignmentId`, `TicketAuditLogId`, etc.

### 1.4 Enums (7)
- [x] `TicketStatus` — ✅ lowercase values (case names UPPER_SNAKE_CASE es estándar PHP)
- [x] `ServiceType` — ✅ lowercase values
- [x] `JourneyType` — ✅ lowercase values
- [x] `EvidenceVisibility` — ✅ lowercase values
- [x] `MaintenanceFrequencyType` — ✅ lowercase
- [x] `RescheduleRequestedBy` — ✅ lowercase values
- [x] `WaitingCategory` — ✅ lowercase values

### 1.5 Domain Events (21)
- [x] Todos implementados

### 1.6 Repositories (15 interfaces)
- [x] `TicketRepositoryInterface`, `TicketAssignmentRepositoryInterface`, etc.
- [x] `QuickTemplateRepositoryInterface`, `RmaRecordRepositoryInterface`, `ScheduledMaintenanceRepositoryInterface`

### 1.7 Exceptions (20)
- [x] `AssignmentNotFoundException`, `DuplicateCatCodeException`, `EmployeeNotAuthorizedException`, etc.

---

## TASK-02: Application Layer

### 2.1 Commands (33)
- [x] Todos los commands + handlers implementados

### 2.2 Queries (16)
- [x] Todos los queries + handlers implementados
- [x] 16 Response DTOs

### 2.3 Reports
- [x] `TechnicianReportGenerator` — reporte de rendimiento de técnicos (DBAL)

### 2.4 Application Exceptions (7)
- [x] `EvidenceNotFoundException`, `FindingNotFoundException`, `TicketNotFoundException`, etc.

---

## TASK-03: Infrastructure Layer

### 3.1 Doctrine Repositories (15)
- [x] Todas las implementaciones

### 3.2 ORM Mappings (18 total)
- [x] `Ticket.orm.xml` — table `tickets`
- [x] `TicketAssignment.orm.xml`
- [x] `TicketEvidence.orm.xml`
- [x] `TicketFinding.orm.xml`
- [x] `TicketPause.orm.xml`, `TicketReschedule.orm.xml`
- [x] `TicketSparePart.orm.xml`, `TicketPendingSparePart.orm.xml`
- [x] `Journey.orm.xml`
- [x] `RmaRecord.orm.xml`, `ScheduledMaintenance.orm.xml`
- [x] `TicketAuditLog.orm.xml`
- [x] `TicketWaiting.orm.xml`
- [x] `QuickTemplate.orm.xml`
- [x] `QuickTemplateItem.orm.xml`
- [x] `FindingComponent.orm.xml`
- [x] `FindingEvidence.orm.xml`
- [x] `TicketModuleFinding.orm.xml`

**Fix aplicado**: `QuickTemplate.orm.xml` — corregido `<join-column>` inválido dentro de `<one-to-many>`.
**Fix aplicado**: `TicketModuleFinding.orm.xml` — agregado `<many-to-one>` a `Ticket`.
**Fix aplicado**: `TicketWaiting.orm.xml`, `TicketModuleFinding.orm.xml`, `FindingEvidence.orm.xml` — enums mapeados como `type="string"` cambiados a custom DBAL types (`support_waiting_category`, `support_finding_type`, `support_evidence_moment`).

### 3.3 Custom DBAL Types (24)
- [x] Todos los tipos implementados

### 3.4 Console Commands
- [x] `support:monitor-sla`
- [x] `support:evaluate-maintenance`

### 3.5 Event Subscribers
- [x] `LogModuleFindingSubscriber`

---

## TASK-04: Presentation Layer

### 4.1 API Controllers
- [x] `ApiSupportTicketController` — ~16 endpoints
- [x] `ApiSupportTicketModuleFindingController` — ATM module tree + findings CRUD
- [x] `ApiSupportQuickTemplateController` — CRUD quick templates
- [x] `ApiSupportRmaController` — list + create RMA
- [x] `ApiSupportDashboardController` — dashboard summary

### 4.2 Web Controllers
- [x] `TicketController`, `TicketActionController`
- [x] `SupportDashboardController`, `SupportReportController`
- [x] `QuickTemplateController`, `ClientPortalController`

### 4.3 Templates
- [x] `support/ticket/list.html.twig`, `show.html.twig`, `form.html.twig`
- [x] `support/portal/tickets.html.twig`, `show.html.twig`
- ❌ `support/dashboard/index.html.twig` — **POSIBLEMENTE FALTANTE**
- ❌ `support/quick_template/list.html.twig` — **POSIBLEMENTE FALTANTE**

---

## TASK-05: Tests

### 5.1 Unit Tests (43 files, 284 tests, 691 assertions)
- [x] `TicketTest`, `TicketAssignmentTest`, `TicketAuditLogTest` + 14 more entity tests
- [x] 18 ValueObject tests (todos los ULID VOs)
- [x] `ApiSupportTicketModuleFindingControllerTest`

**Command Handler Tests (16):**
- [x] `ApproveTicketHandlerTest`, `ArriveAtBaseHandlerTest`
- [x] `ArriveAtSiteHandlerTest`, `AssignTicketHandlerTest`
- [x] `CancelTicketHandlerTest`, `CompleteAssistanceHandlerTest`
- [x] `CreateQuickTemplateHandlerTest`, `CreateTicketHandlerTest`
- [x] `PauseTicketHandlerTest`, `RecordModuleFindingHandlerTest`
- [x] `RegisterFindingHandlerTest`, `RescheduleTicketHandlerTest`
- [x] `ResumeTicketHandlerTest`, `StartOutwardJourneyHandlerTest`
- [x] `UpdateModuleFindingHandlerTest`, `UpdateQuickTemplateHandlerTest`

**Faltantes (baja prioridad):**
- ~16 command handlers sin test (ApplyQuickTemplate, ReassignTicket, SoftDelete, etc.)
- ~18 query handlers sin test (ListTickets, GetTicketDetail, etc.)

---

## TASK-06: Known Gaps

| # | Gap | Estado |
|---|-----|--------|
| G‑01 | Enum values en UPPERCASE (violan convención lowercase) | ✅ Los string values ya eran lowercase. Sin cambios requeridos. |
| G‑02 | 7 ORM mappings faltantes | ✅ Todos los 18 .orm.xml existen y están corregidos (sintaxis, missing ManyToOne, enum types) |
| G‑03 | Tests coverage insuficiente (solo 5 archivos) | ✅ 43 test files (284 tests, 691 assertions). 16 handlers testeados. Faltan ~34 handlers/queries. |
| G‑04 | Templates de dashboard y quick_template | ❌ Pendiente de verificar |
| G‑05 | Enum fields tipo `string` en ORM (PHP 8.4) | ✅ Creados 3 DBAL types: `WaitingCategoryType`, `FindingTypeType`, `EvidenceMomentType` |
| G‑06 | `TicketModuleFinding` sin ManyToOne a Ticket | ✅ Agregado en ORM XML |
| G‑07 | `QuickTemplate.orm.xml` sintaxis inválida | ✅ Corregido (`<join-column>` dentro de `<one-to-many>` removido) |
