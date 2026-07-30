# Tasks — Organization Management

**Spec**: `specs/001-organization-spec/spec.md`
**Código**: `src/app/Organization/`
**Tests**: 55 test files (Unit)
**Estimado**: ~95% implementado

---

## Leyenda
- ✅ Completado
- ❌ Pendiente

---

## TASK-01: Domain Layer

### 1.1 Entities
- [x] `Employee` — Aggregate root con ciclo de vida completo
- [x] `ServiceZone` — Zona de servicio geográfica
- [x] `ServiceZoneAssignment` — Auditoría de asignaciones (immutable)
- [x] `VacationRequest` — Solicitud de vacaciones con aprobación/rechazo
- [x] `OvertimeRecord` — Horas extras con ciclo de revisión
- [x] `EmploymentPeriod` — Períodos de empleo (historial)
- [x] `EmployeeSpecialPeriod` — Períodos VACATION y STANDBY

### 1.2 Value Objects
- [x] 10 VOs: `EmployeeId`, `EmployeeIdentificationNumber`, `EmployeeEmail`, `EmployeeVacationPeriod`, `EmployeeSpecialPeriodId`, `EmploymentPeriodId`, `OvertimeRecordId`, `ServiceZoneAssignmentId`, `ServiceZoneId`, `VacationRequestId`

### 1.3 Enums
- [x] `EmployeeProfile` — `technician`, `coordinator`, `administrative`
- [x] `EmployeeStatus` — `active`, `on_vacation`, `inactive`
- [x] `ServiceZoneStatus` — `active`, `inactive`
- [x] `OvertimeStatus` — `pending`, `in_review`, `approved`, `rejected`
- [x] `EmploymentPeriodStatus` — `active`, `closed`
- [x] `EmployeeSpecialPeriodType` — `vacation`, `standby`
- [x] `EmployeeSpecialPeriodStatus` — `scheduled`, `in_progress`, `completed`, `cancelled`
- [x] `VacationRequestStatus` — `pending`, `approved`, `rejected`, `cancelled`

### 1.4 Domain Events
- [x] 23 eventos: Employee lifecycle (6), ServiceZone (3), Overtime (5), SpecialPeriod (5), VacationRequest (4), CityTransferred (1)

### 1.5 Repositories
- [x] 7 interfaces: `EmployeeRepository`, `ServiceZoneRepository`, `ServiceZoneAssignmentRepository`, `OvertimeRecordRepository`, `EmploymentPeriodRepository`, `EmployeeSpecialPeriodRepository`, `VacationRequestRepository`

### 1.6 Exceptions
- [x] 19 excepciones de dominio

---

## TASK-02: Application Layer

### 2.1 Commands
- [x] 32 commands + handlers

### 2.2 Queries
- [x] 8 queries + handlers + response DTOs

### 2.3 Application Exceptions
- [x] `EmployeeNotFoundException`, `EmployeeDuplicateIdentificationException`, `EmployeeDuplicateEmailException`, `TerminationBlockedException`, `ServiceZoneNotFoundException`

### 2.4 Application Services
- [x] `TerminationVerificationService` — verifica blockers antes de terminar empleado

---

## TASK-03: Infrastructure Layer

### 3.1 Doctrine Repositories
- [x] 7 implementaciones

### 3.2 ORM Mappings
- [x] `Employee.orm.xml` — table `employees`
- [x] `ServiceZone.orm.xml` — table `service_zones`
- [x] `ServiceZoneAssignment.orm.xml` — table `org_service_zone_assignments`
- [x] `EmployeeSpecialPeriod.orm.xml` — table `employee_special_periods`
- [x] `EmploymentPeriod.orm.xml` — table `employment_periods`
- [x] `OvertimeRecord.orm.xml` — table `overtime_records`
- [x] `VacationRequest.orm.xml` — table `vacation_requests`

### 3.3 Custom DBAL Types
- [x] 17 tipos

### 3.4 Console Commands
- [x] `SeedDevCommand`, `SeedDemoDataCommand`, `SeedFullDataCommand`
- [x] `ProcessEmployeeSpecialPeriodsCommand`

### 3.5 Import
- [x] `EmployeeCsvFileParser`, `EmployeeRowValidator`

### 3.6 Messenger
- [x] `EmployeeStatusCascadeHandler` — async

### 3.7 Event Listeners
- [x] `UserStatusCascadeOnEmployeeChangeListener` — sync (dual con Messenger)

---

## TASK-04: Presentation Layer

### 4.1 API Controllers
- [x] `ApiOrganizationController` — CRUD employees + vacation + overtime + dashboard
- [x] `ApiServiceZoneController` — CRUD service zones
- [x] `ApiOrganizationImportController` — import employees

### 4.2 Web Controllers
- [x] `EmployeeController`, `ServiceZoneController`, `OrganizationImportController`

### 4.3 Templates
- [x] `employee/list.html.twig`, `employee/show.html.twig`, `employee/form.html.twig`
- [x] `service_zone/list.html.twig`, `service_zone/show.html.twig`, `service_zone/form.html.twig`
- [x] `organization/import.html.twig` — creado

---

## TASK-05: Tests

### 5.1 Unit Tests (55 files)
- [x] 8 Entity tests (Employee, ServiceZone, etc.)
- [x] 10 ValueObject tests
- [x] 28 Command handler tests
- [x] 8 Query handler tests (todos los queries cubiertos)
- [x] 1 Cross-BC test (EmployeeStatusCascadeHandler)

---

## TASK-06: Known Gaps

| # | Gap | Estado |
|---|-----|--------|
| G‑01 | EmploymentPeriod, OvertimeRecord, VacationRequest sin ORM mapping | ✅ Corregido |
| G‑02 | `organization/import.html.twig` no existe | ✅ Creado |
| G‑03 | Dual cascade (sync+async) para Employee→User — unificar | ✅ Corregido: eliminado listener sync, async handler usa userId del evento directo |
| G‑04 | `ListEmployeesQuery` sin propiedades de filtro | ✅ Agregados profile, status, search |
