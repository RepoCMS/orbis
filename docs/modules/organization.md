# Module: Organization

## Overview

The **Organization** Bounded Context manages company structure, employees, service zones, and geographic coverage within the Orbis platform. It integrates with Identity (user provisioning), Support (technician assignment), and Shared Kernel (dashboard metrics).

## Core Responsibilities

- Employee lifecycle management
- Service zone definition and technician assignment
- Geographic coverage (regions, provinces, cities, zones)
- Vacation and overtime tracking
- Special period scheduling (standbys, training, etc.)
- Cross-BC user provisioning

## Status

- **Progress:** ~67%
- **Tests:** ~60 files
- **Reports:** ActivePersonnel, Overtime, ScheduledVacations
- **Importers:** Import Employees
- **Dashboard:** Web exists

## Domain Model

### Aggregate: Employee

| Field | Type | Description |
|-------|------|-------------|
| `id` | `EmployeeIdValueObject` (ULID) | Unique identifier |
| `firstName` | `FirstNameValueObject` | First name |
| `lastName` | `LastNameValueObject` | Last name |
| `documentId` | `DocumentIdValueObject` | ID number |
| `documentType` | `DocumentType` | `national_id` / `passport` etc. |
| `email` | `EmailValueObject` | Email address |
| `status` | `EmployeeStatus` | `active` / `inactive` |
| `assignedUserId` | `?UserIdValueObject` | Linked Identity user |
| `createdAt` | `DateTimeImmutable` | Creation timestamp |

### Aggregate: ServiceZone

Geographic coverage zones with assigned technicians and cities.

### Aggregate: City / Province / Region

Hierarchical geographic data.

## Commands

- `CreateEmployee` — Create new employee
- `UpdateEmployee` — Update employee info
- `DeactivateEmployee` — Deactivate
- `ReactivateEmployee` — Reactivate
- `SoftDeleteEmployee` — Soft delete
- `LinkUserToEmployee` — Link to Identity user
- `UnlinkUserFromEmployee` — Remove link
- `ProvisionEmployeeAccess` — Provision Identity user for employee
- `ImportEmployees` — Import from CSV/Excel
- `CreateServiceZone` — Create zone
- `UpdateServiceZone` — Update zone
- `DeleteServiceZone` — Delete zone
- `ActivateServiceZone` — Activate zone
- `ReactivateServiceZone` — Reactivate zone
- `DeactivateServiceZone` — Deactivate zone
- `AddCityToZone` — Add city to zone
- `RemoveCityFromZone` — Remove city
- `AssignTechnicianToZone` — Assign technician
- `RemoveTechnicianFromZone` — Remove assignment
- `TransferCity` — Move city between zones
- `ScheduleEmployeeVacation` — Schedule vacation
- `ApproveVacationRequest` — Approve
- `RejectVacationRequest` — Reject
- `CancelVacationRequest` — Cancel
- `RegisterOvertime` — Record overtime
- `ApproveOvertime` — Approve
- `RejectOvertime` — Reject
- `CorrectOvertime` — Correct entry
- `RequestOvertimeCorrection` — Request correction
- `CancelEmployeeSpecialPeriod` — Cancel special period
- `CompleteEmployeeSpecialPeriod` — Complete
- `EditEmployeeSpecialPeriod` — Edit
- `ScheduleEmployeeStandby` — Schedule standby

## Queries

- `ListEmployees` — Paginated list
- `FindEmployeeById` — Find by ID
- `ListServiceZones` — List zones
- `FindServiceZoneById` — Find zone
- `ListEmployeeSpecialPeriods` — Special periods
- `ListVacationRequestsByEmployee` — Vacation requests
- `ListOvertimeRecordsByEmployee` — Overtime records
- `GetOrganizationDashboard` — Dashboard KPIs

## Cross-BC Integrations

| BC | Direction | Interaction |
|----|-----------|-------------|
| Identity | Read/Write | `ProvisionEmployeeAccessHandler` creates users; resolve emails |
| Identity | Write | Links users to employees |
| Support | Read | Technician assignment zones |
| Shared Kernel | Both | Dashboard metrics, importer infrastructure |

## Reports

1. **ActivePersonnel** — Active employee counts
2. **Overtime** — Overtime records report
3. **ScheduledVacations** — Upcoming vacations

## Importers

1. **Import Employees** — Employee CSV/Excel import

## Pending Gaps

| Gap | Priority |
|-----|----------|
| Reportes P2 pendientes | High |
| Spec alignment (~67% → target 95%) | High |
| Missing test coverage | Medium |

## API Endpoints

| Method | Route | Permission |
|--------|-------|------------|
| GET | `/api/v1/organization/employees` | `employees.view` |
| POST | `/api/v1/organization/employees` | `employees.create` |
| GET | `/api/v1/organization/employees/{id}` | `employees.view` |
| POST | `/api/v1/organization/employees/{id}/link-user` | `employees.update` |
| GET | `/api/v1/organization/zones` | `zones.view` |
| GET | `/api/v1/organization/dashboard` | `organization.view_dashboard` |

*(Full API endpoint list in spec)*