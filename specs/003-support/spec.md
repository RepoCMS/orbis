# Feature Specification: Support (Ticketing & Field Service)

**Spec path**: `specs/003-support/spec.md`
**Created**: 2026-06-17
**Status**: In Development (~85% implementado)

---

## 1. Descripción General

El Bounded Context **Support** gestiona el ciclo de vida de tickets de servicio técnico de campo. Cubre desde la creación del ticket hasta el cierre, incluyendo asignación de técnicos, viajes, diagnósticos, uso de repuestos, SLAs y portal de cliente.

Responsabilidades:
- Ciclo de vida completo de tickets (11 estados)
- Asignación y reasignación de técnicos
- Gestión de viajes (ida, múltiples sitios, regreso)
- Registro de hallazgos/diagnósticos (findings y module findings)
- Solicitud y uso de repuestos
- Pausas, esperas, reprogramaciones
- SLAs (monitoreo de vencimiento y breach)
- Plantillas rápidas (QuickTemplates) por modelo de ATM
- RMA (Return Merchandise Authorization)
- Mantenimiento programado
- Portal de cliente para seguimiento
- Reportes de rendimiento de técnicos

---

## 2. Business Rules

| # | Regla | Descripción |
|---|-------|-------------|
| SU‑01 | **Máquina de estados** | Ticket tiene 11 estados con transiciones válidas definidas. Cancelable desde cualquier estado no-terminal. |
| SU‑02 | **Reasignación** | Solo permitida desde CREATED o ASSIGNED. Una vez iniciado viaje/espera/atención NO se puede reasignar. |
| SU‑03 | **Jerarquía de tickets** | Un ticket puede tener tickets hijos (subtickets) y un ticket padre. |
| SU‑04 | **SLA por cliente+contrato** | `slaDeadlineAt` se calcula al crear el ticket. No bloquea el flujo. |
| SU‑05 | **Repuestos** | finding y spare-parts son endpoints separados (UX fluido). |
| SU‑06 | **Evidencia multimedia** | Las evidencias pueden ser imágenes o videos. Se validan por mime type. |
| SU‑07 | **Hallazgos por módulo** | Los module findings siguen el árbol de módulos del modelo de ATM (spec 013). |
| SU‑08 | **Auditoría** | Cada acción sobre un ticket se registra en `TicketAuditLog`. |
| SU‑09 | **Portal de cliente** | Los clientes solo ven sus propios tickets (scoping por `clientId`). |

---

## 3. Estado Machine (Ticket)

```
CREATED → ASSIGNED → IN_TRANSIT_OUT → WAITING → IN_ASSISTANCE
                                                         ↓
                                            (PAUSED ⇄ RESUME)
                                                         ↓
                                             IN_TRANSIT_BACK → PENDING_CLOSURE → CLOSED

Cualquier estado no-terminal → CANCELLED
```

Transiciones válidas definidas en `TicketStatus` con métodos `canBeCancelled()`, `isTerminal()`.

---

## 4. Aggregates

### 4.1 Ticket (Aggregate Root — 506 lines)

| Campo | Tipo |
|-------|------|
| `id` | `TicketIdValueObject` (ULID) |
| `ticketNumber` | `string` (secuencia auto-generada) |
| `clientId` | `ClientIdValueObject` (Client BC) |
| `terminalId` | `TerminalIdValueObject` (Client BC) |
| `serviceType` | `ServiceType` |
| `status` | `TicketStatus` |
| `scheduledAt` | `?DateTimeImmutable` |
| `slaDeadlineAt` | `?DateTimeImmutable` |
| `assignedTo` | `EmployeeIdValueObject[]` (Organization BC) |
| `createdByUserId` | `UserIdValueObject` (Identity BC) |

**Métodos clave:** `create()`, `assign()`, `reassign()`, `startOutwardJourney()`, `arriveAtSite()`, `startWaiting()`, `startAssistance()`, `pause()`, `resume()`, `completeAssistance()`, `arriveAtBase()`, `approve()`, `cancel()`, `reschedule()`, `softDelete()`, `reopen()`, `requestReopen()`

### 4.2 TicketModuleFinding

| Campo | Tipo |
|-------|------|
| `id` | `TicketModuleFindingIdValueObject` (ULID) |
| `ticketId` | `TicketIdValueObject` |
| `modulePath` | `ModulePathValueObject` (ej. `"BRM > BMU > BC"`) |
| `findingType` | `FindingType` (ok / faulty / replaced / pending) |
| `evidence` | `FindingEvidence[]` (imágenes/videos) |

### 4.3 QuickTemplate

| Campo | Tipo |
|-------|------|
| `id` | `QuickTemplateIdValueObject` (ULID) |
| `modelId` | `TerminalModelIdValueObject` (Client BC) |
| `name` | `string` |
| `items` | `QuickTemplateItem[]` (checklist items) |

---

## 5. Commands (33)

| ID | Command | Descripción |
|----|---------|-------------|
| C‑01 | `CreateTicket` | Crea ticket con datos básicos |
| C‑02 | `AssignTicket` | Asigna técnico(s) al ticket |
| C‑03 | `ReassignTicket` | Reasigna técnico (solo en CREATED/ASSIGNED) |
| C‑04 | `StartOutwardJourney` | Inicia viaje de ida |
| C‑05 | `ArriveAtSite` | Llega al sitio del cliente |
| C‑06 | `RegisterFinding` | Registra hallazgo/diagnóstico |
| C‑07 | `RecordModuleFinding` | Registra hallazgo de módulo ATM |
| C‑08 | `UpdateModuleFinding` | Actualiza hallazgo de módulo |
| C‑09 | `StartWaiting` | Inicia período de espera |
| C‑10 | `StartAssistanceFromWaiting` | Inicia atención desde espera |
| C‑11 | `PauseTicket` | Pausa atención |
| C‑12 | `ResumeTicket` | Reanuda atención |
| C‑13 | `CompleteAssistance` | Completa atención |
| C‑14 | `ArriveAtBase` | Llega a base |
| C‑15 | `ApproveTicket` | Aprueba y cierra ticket |
| C‑16 | `CancelTicket` | Cancela ticket |
| C‑17 | `RescheduleTicket` | Reprograma ticket |
| C‑18 | `SoftDeleteTicket` | Soft delete |
| C‑19 | `RequestReopenTicket` | Solicita reapertura |
| C‑20 | `ApproveReopenTicket` | Aprueba reapertura |
| C‑21 | `AddEvidence` | Agrega evidencia |
| C‑22 | `UploadFindingEvidence` | Sube evidencia para finding |
| C‑23 | `RegisterSparePart` | Registra repuesto usado |
| C‑24 | `RequestPendingSparePart` | Solicita repuesto pendiente |
| C‑25 | `CreateQuickTemplate` | Crea plantilla rápida |
| C‑26 | `UpdateQuickTemplate` | Actualiza plantilla |
| C‑27 | `DeleteQuickTemplate` | Elimina plantilla |
| C‑28 | `ApplyQuickTemplate` | Aplica plantilla a ticket |
| C‑29 | `CreateRmaRecord` | Crea registro RMA |
| C‑30 | `EvaluateScheduledMaintenance` | Evalúa mantenimiento programado |
| C‑31 | `MonitorSla` | Monitorea SLAs (breach/expiración) |
| C‑32 | `UpdateTicket` | Actualiza campos del ticket |

---

## 6. Queries (16)

| ID | Query | Response |
|----|-------|----------|
| Q‑01 | `ListTickets` | `TicketResponse[]` (paginado + filtros) |
| Q‑02 | `GetTicketDetail` | `TicketResponse` completo |
| Q‑03 | `ListTicketsByStatus` | `TicketResponse[]` |
| Q‑04 | `TicketsByRoute` | Tickets agrupados por ruta |
| Q‑05 | `GetTicketParent` | Ticket padre |
| Q‑06 | `ListTicketChildren` | Tickets hijos |
| Q‑07 | `ListAuditLog` | `TicketAuditLogResponse[]` |
| Q‑08 | `ListEvidences` | `TicketEvidenceResponse[]` |
| Q‑09 | `GetEvidence` | `TicketEvidenceResponse` |
| Q‑10 | `ListTicketModuleFindings` | `TicketModuleFindingResponse[]` |
| Q‑11 | `GetAtmModuleTree` | `ModuleTreeResponse` (árbol por modelo) |
| Q‑12 | `GetCatCodeComponents` | Componentes CAT code |
| Q‑13 | `ListQuickTemplates` | `QuickTemplateResponse[]` |
| Q‑14 | `FindQuickTemplate` | `QuickTemplateResponse` |
| Q‑15 | `ListQuickTemplatesByModel` | `QuickTemplateResponse[]` |
| Q‑16 | `ListRmaRecords` | `RmaRecordResponse[]` |
| Q‑17 | `SupportDashboardMetrics` | KPIs del dashboard |
| Q‑18 | `GetActiveTerminalAssignments` | Asignaciones activas |

---

## 7. Domain Events (21)

| Evento | Disparado por |
|--------|---------------|
| `TicketCreated` | C‑01 |
| `TicketAssigned` | C‑02 |
| `TicketReassigned` | C‑03 |
| `TicketJourneyStarted` | C‑04 |
| `TicketArrivedAtSite` | C‑05 |
| `TicketArrivedAtBase` | C‑14 |
| `TicketAssistanceStarted` | C‑10 |
| `TicketWaitingStarted` | C‑09 |
| `TicketPaused` | C‑11 |
| `TicketResumed` | C‑12 |
| `TicketAssistanceCompleted` | C‑13 |
| `TicketClosed` | C‑15 |
| `TicketCancelled` | C‑16 |
| `TicketRescheduled` | C‑17 |
| `TicketReopened` | C‑20 |
| `TicketReopenRequested` | C‑19 |
| `TicketDeleted` | C‑18 |
| `TicketFindingRegistered` | C‑06 |
| `TicketModuleFindingRecorded` | C‑07 |
| `SlaAboutToExpire` | C‑31 |
| `SlaBreached` | C‑31 |

---

## 8. Cross-BC Interactions

| BC | Tipo | Interacción |
|----|------|-------------|
| **Client** | Lectura | `clientId`, `terminalId`, `terminalModelId` referenciados |
| **Organization** | Lectura | `assignedTo` empleados, técnicos disponibles |
| **Inventory** | Lectura | Stock de repuestos vía `InsufficientStockException` |
| **Identity** | Lectura | `createdByUserId` referencia User |
| **Notification** | Evento | SLA events → Notification BC (cuando esté implementado) |

---

## 9. Known Gaps

| # | Gap | Prioridad |
|---|-----|-----------|
| G‑01 | **Enum values en UPPERCASE**: Los string values ya eran lowercase. Sin cambios requeridos. ✅ | Cerrado |
| G‑02 | **ORM mappings faltantes**: Todos los 18 `.orm.xml` existen, corregidos (sintaxis, missing ManyToOne, enum types) ✅ | Cerrado |
| G‑03 | **Tests**: 43 test files (284 tests, 691 assertions). 16 handlers testeados. Faltan ~34 handlers/queries | Media |
| G‑04 | **Application/Exception/** existe con 7 archivos ✅ | Cerrado |
| G‑05 | **SLA Monitoring**: Handler existe pero no está conectado a Notification BC | Media |
| G‑06 | **Client Portal**: Controladores existen, templates básicos, falta refinamiento UX | Media |
| G‑07 | **Reporte PDF/Excel**: `TechnicianReportGenerator` existe, falta UI de descarga completa | Baja |
| G‑08 | **Enum fields tipo `string`** en ORM (PHP 8.4): Creados 3 custom DBAL types (WaitingCategory, FindingType, EvidenceMoment) ✅ | Cerrado |
