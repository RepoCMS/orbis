# Module: Support

## Overview

The **Support** Bounded Context manages technical support operations including ticket lifecycle, technician assignment, ATM module tree maintenance, evidence management, and SLA monitoring. It integrates with Inventory (spare parts), Location (geography), Organization (technicians), and Client (client context).

## Core Responsibilities

- Support ticket creation and lifecycle management
- Technician assignment and zone-based routing
- ATM module tree (hierarchical configuration)
- Evidence collection and management
- SLA monitoring and enforcement
- Spare part usage tracking
- Quick response templates
- Service zone and geographic coverage

## Status

- **Progress:** ~85%
- **Tests:** ~89 files
- **Reports:** Technician, TicketsByClient
- **Importers:** None
- **Dashboard:** Expand WIP

## Domain Model

### Aggregate: Ticket

| Field | Type | Description |
|-------|------|-------------|
| `id` | `TicketIdValueObject` (ULID) | Unique identifier |
| `ticketNumber` | `string` | Sequential number |
| `clientSiteId` | `SiteIdValueObject` | Client site |
| `status` | `TicketStatus` | `new` / `assigned` / `in_progress` / `on_hold` / `resolved` / `cancelled` |
| `priority` | `TicketPriority` | `low` / `medium` / `high` / `critical` |
| `category` | `string` | Category type |
| `assignedTechnicianId` | `?EmployeeIdValueObject` | Assigned technician |
| `createdAt` | `DateTimeImmutable` | Creation timestamp |
| `slaDeadline` | `DateTimeImmutable` | SLA deadline |

### Aggregate: Technician

Links Organization employees to support zones and skills.

### Aggregate: ModuleNode

Hierarchical tree structures for ATM modules, with parent-child relationships, component types, and spare part associations.

### Aggregate: Evidence

Supports images and documents attached to tickets/tickets.

### Aggregate: ServiceZone

Geographic coverage zones assigned to technicians.

## Commands

- `CreateTicket` — Create new support ticket
- `UpdateTicket` — Update ticket details
- `AssignTicket` — Assign to technician
- `ReassignTicket` — Reassign to different technician
- `StartWaiting` — Move to waiting state
- `CompleteAssistance` — Mark as complete
- `ApproveTicket` — Approve resolution
- `RejectTicket` — Reject resolution
- `CancelTicket` — Cancel ticket
- `PauseTicket` — Pause ticket
- `ResumeTicket` — Resume paused ticket
- `RequestReopenTicket` — Request reopening
- `ApproveReopenTicket` — Approve reopening
- `RejectReopenTicket` — Reject reopening
- `ArriveAtBase` — Technician arrives at base
- `ArriveAtSite` — Technician arrives at site
- `StartOutwardJourney` — Begin outward journey
- `RegisterSparePart` — Register spare part usage
- `RequestPendingSparePart` — Request missing part
- `RegisterFinding` — Register technical finding
- `UpdateModuleFinding` — Update finding details
- `AddEvidence` — Upload evidence
- `UploadFindingEvidence` — Upload evidence for finding
- `RemoveEvidence` — Remove evidence
- `ApplyQuickTemplate` — Apply template
- `CreateQuickTemplate` — Create template
- `UpdateQuickTemplate` — Update template
- `DeleteQuickTemplate` — Delete template
- `ListQuickTemplatesByModel` — List templates for model
- `CreateRmaRecord` — Create RMA record
- `MonitorSla` — SLA monitoring (cron)
- `EvaluateScheduledMaintenance` — Scheduled maintenance (cron)
- `CreateModuleNode` — Create ATM module node
- `UpdateModuleNode` — Update module node
- `DeleteModuleNode` — Delete module node
- `RecordModuleFinding` — Record finding against module
- `ScheduleEmployeeStandby` — Schedule standby
- `StartAssistanceFromWaiting` — Start assistance from waiting

## Queries

- `ListTickets` — Paginated ticket list
- `FindTicketById` — Find ticket by ID
- `ListTicketsByStatus` — Filter by status
- `GetTicketDetail` — Detailed view
- `GetTicketParent` — Parent ticket
- `ListTicketChildren` — Child tickets
- `ListTicketModuleFindings` — Findings for module
- `ListTicketPendingSpareParts` — Pending parts
- `ListTicketSpareParts` — Used parts
- `ListEvidences` — Evidence list
- `GetEvidence` — Get evidence
- `GetAtmModuleTree` — Hierarchical structure
- `ListAuditLog` — Audit trail
- `ListQuickTemplates` — Quick templates
- `ListQuickTemplatesByModel` — Per-model templates
- `ListRmaRecords` — RMA records
- `ListServiceZones` — Service zones
- `GetActiveTerminalAssignments` — Active assignments
- `GetCatCodeComponents` — Category components
- `TicketsByRoute` — Tickets by route
- `SupportDashboardMetrics` — Dashboard KPIs

## Cross-BC Integrations

| BC | Direction | Interaction |
|----|-----------|-------------|
| Organization | Read | Technician assignment, zones |
| Inventory | Read/Write | Spare part usage, emergency parts |
| Location | Read | Geographic context |
| Client | Read | Client/site context |
| Identity | Read | User context |

## Reports

1. **Technician** — Technician performance metrics
2. **TicketsByClient** — Ticket counts per client

## API Endpoints

| Method | Route | Permission |
|--------|-------|------------|
| GET | `/api/v1/support/tickets` | `tickets.view` |
| POST | `/api/v1/support/tickets` | `tickets.create` |
| GET | `/api/v1/support/tickets/{id}` | `tickets.view` |
| PATCH | `/api/v1/support/tickets/{id}` | `tickets.update` |
| POST | `/api/v1/support/tickets/{id}/assign` | `tickets.assign` |
| GET | `/api/v1/support/technicians` | `technicians.view` |
| GET | `/api/v1/support/queue` | `tickets.view` |
| GET | `/api/v1/support/dashboard` | `support.view_dashboard` |

*(Full API endpoint list in spec)*