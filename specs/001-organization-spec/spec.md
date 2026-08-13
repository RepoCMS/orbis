# Feature Specification: Organization Management

**Feature Branch**: `001-organization-spec`

**Created**: 2026-06-17

**Status**: In Development (~95% implementado — G-06..G-09 resueltos 2026-08-12, pendientes G-10..G-14)

> **Nota de trazabilidad:** Este spec fue alineado con la realidad del código el 2026-08-07 (tarea T02 del tablero). Conteos verificados: 33 Commands, 8 Queries, 8 Controllers, 3 Reportes, 1 Importer, 25 Domain Events, 7 ORM mappings, 63 archivos de test. El invariante Employee↔User está especificado por separado en `specs/010a-employee-user-invariant/spec.md`.

---

## 1. Descripción General

El Bounded Context **Organization** gestiona los recursos humanos y la estructura organizacional de ACMS. Cubre empleados, zonas de servicio, asignaciones, vacaciones, horas extras y períodos especiales (standby).

Responsabilidades:
- Registro y ciclo de vida de empleados (técnicos, coordinadores, administrativos)
- Vinculación de empleados con usuarios del sistema (Identity BC)
- Gestión de zonas de servicio geográficas
- Asignación de técnicos a zonas
- Solicitud y aprobación de vacaciones
- Registro y aprobación de horas extras
- Períodos especiales (vacaciones programadas, standby en feriados)
- Importación masiva de empleados
- Dashboard de KPIs organizacionales
- Reportes exportables (Personal Activo, Vacaciones Programadas, Horas Extras)

---

## 2. Business Rules

| # | Regla | Descripción |
|---|-------|-------------|
| OR‑01 | **Identificación única** | No pueden existir dos empleados con la misma identificación. |
| OR‑02 | **Email único** | El email corporativo debe ser único por empleado. |
| OR‑03 | **Ciclo de vida empleado** | ACTIVE → INACTIVE (al terminar). ON_VACATION se actualiza automáticamente por el proceso de períodos. |
| OR‑04 | **Vinculación única con User** | Un empleado puede vincularse a un User de Identity BC (1:1). Ver spec 010a. |
| OR‑05 | **Perfil solo descriptivo** | El profile (technician/coordinator/administrative) es informativo, no autorizativo. La autorización va por Role en Identity BC. |
| OR‑06 | **Zona de servicio** | Solo técnicos pueden ser asignados a una zona (`Employee::assignToZone` lanza si no es technician). Un empleado pertenece a una sola zona; múltiples técnicos por zona. |
| OR‑07 | **Vacaciones** | Solicitud → Aprobación → Período especial VACATION → ON_VACATION (al iniciar). Rechazo mantiene ACTIVE. |
| OR‑08 | **Standby** | Período de trabajo en feriado/fin de semana. NO cambia el status del empleado. |
| OR‑09 | **Horas extras** | Registro → Revisión → Aprobación/Rechazo. Puede solicitar corrección (IN_REVIEW → PENDING). |
| OR‑10 | **Terminación bloqueada** | Un empleado con tickets abiertos (Support) o períodos especiales activos no puede ser terminado (`TerminationVerificationService`). |
| OR‑11 | **Procesamiento automático** | `org:process-special-periods` (cron) auto-procesa transiciones SCHEDULED→IN_PROGRESS→COMPLETED. VACATION cambia status del empleado (ON_VACATION / ACTIVE); STANDBY no. |
| OR‑12 | **Cascada Employee → User** | Desactivar/eliminar/reactivar un empleado cascada a su User vinculado (Identity) vía Messenger async (`EmployeeStatusCascadeHandler`). |
| OR‑13 | **Período especial válido** | `endedAt` debe ser posterior a `startedAt`. Si `startedAt` ya pasó, el período nace en IN_PROGRESS. |
| OR‑14 | **Cambio de perfil** | Si un técnico deja de ser técnico, se desvincula de su zona automáticamente (`changeProfile`). |

---

## 3. Aggregates

> Verificado en disco: 7 entidades en `src/app/Organization/Domain/Entities/`.

### 3.1 Employee (Aggregate Root)

| Campo | Tipo |
|-------|------|
| `id` | `EmployeeIdValueObject` (ULID) |
| `userId` | `?UserIdValueObject` (Identity BC) |
| `identificationNumber` | `EmployeeIdentificationNumberValueObject` |
| `firstName`, `lastName` | `string` (5-100 chars, normalizado) |
| `email` | `EmployeeEmailValueObject` |
| `phoneNumber` | `string` (09XXXXXXXX, 10 dígitos) |
| `status` | `EmployeeStatus` (ACTIVE / ON_VACATION / INACTIVE) |
| `profile` | `EmployeeProfile` (technician / coordinator / administrative) |
| `serviceZoneId` | `?ServiceZoneIdValueObject` |
| `hiredAt` | `DateTimeImmutable` |
| `terminatedAt` | `?DateTimeImmutable` |
| `deletedAt` | `?DateTimeImmutable` (soft delete) |

**Factory:** `Employee::hire(id, identificationNumber, firstName, lastName, email, phoneNumber, hiredAt, status, userId, profile): self`

**State transitions (verificadas):** `linkUser()`, `unlinkUser()`, `canLinkUser()`, `assignToZone()` (solo técnicos), `removeFromZone()`, `terminate()`, `reactivate()`, `startVacation()` (solo ACTIVE), `returnFromVacation()` (solo ON_VACATION), `changeProfile()` (desvincula zona si deja de ser técnico), `changeFirstName()`, `changeLastName()`, `changePhoneNumber()`, `changeEmail()`, `softDelete()`

### 3.2 ServiceZone

| Campo | Tipo |
|-------|------|
| `id` | `ServiceZoneIdValueObject` (ULID) |
| `name` | `string` (3-100 chars, unique) |
| `status` | `ServiceZoneStatus` (ACTIVE / INACTIVE) |
| `cityIds` | `json` (`city_ids` column, nullable) — ULIDs de ciudades de Location BC |
| `deletedAt` | `?DateTimeImmutable` |

Las ciudades se gestionan vía `AddCityToZone`/`RemoveCityFromZone`/`TransferCity` (C-16..C-18).

**Factory:** `ServiceZone::create(id, name): self`

### 3.3 VacationRequest

| Campo | Tipo |
|-------|------|
| `id` | `VacationRequestIdValueObject` (ULID) |
| `employeeId` | `EmployeeIdValueObject` |
| `startDate`, `endDate` | `DateTimeImmutable` |
| `status` | `VacationRequestStatus` (PENDING / APPROVED / REJECTED / CANCELLED) |
| `reviewedBy` | `?EmployeeIdValueObject` |
| `rejectionReason` | `?string` |
| `reviewedAt` | `?DateTimeImmutable` |

**Factory:** `VacationRequest::request(...)`. Al aprobarse se crea un `EmployeeSpecialPeriod` tipo VACATION (ApproveVacationRequestHandler).

### 3.4 OvertimeRecord

| Campo | Tipo |
|-------|------|
| `id` | `OvertimeRecordIdValueObject` (ULID) |
| `employeeId` | `EmployeeIdValueObject` |
| `workedAt` | `DateTimeImmutable` |
| `startTime`, `endTime` | `DateTimeImmutable` |
| `reason` | `string` |
| `status` | `OvertimeStatus` (PENDING / IN_REVIEW / APPROVED / REJECTED) |
| `evidenceUrl` | `?string` |
| `reviewedBy` | `?EmployeeIdValueObject` |
| `rejectionReason` | `?string` |
| `reviewedAt` | `?DateTimeImmutable` |

Los eventos `Overtime*` se registran en la entidad (pullDomainEvents) y se publican desde los handlers.

**Status machine:** PENDING → IN_REVIEW → APPROVED/REJECTED. IN_REVIEW → PENDING (via CorrectOvertime).

### 3.5 EmployeeSpecialPeriod

| Campo | Tipo |
|-------|------|
| `id` | `EmployeeSpecialPeriodIdValueObject` (ULID) |
| `employeeId` | `EmployeeIdValueObject` |
| `type` | `EmployeeSpecialPeriodType` (VACATION / STANDBY) |
| `status` | `EmployeeSpecialPeriodStatus` (SCHEDULED / IN_PROGRESS / COMPLETED / CANCELLED) |
| `startedAt`, `endedAt` | `DateTimeImmutable` |
| `notes` | `?string` |

**Status machine:** SCHEDULED → IN_PROGRESS → COMPLETED (auto-procesado por `org:process-special-periods`; si `startedAt` ya pasó nace IN_PROGRESS). SCHEDULED | IN_PROGRESS → CANCELLED (manual). `completeWithEndDate()` permite completar con fecha real.

### 3.6 EmploymentPeriod (registro de altas/bajas)

| Campo | Tipo |
|-------|------|
| `id` | `EmploymentPeriodIdValueObject` (ULID) |
| `employeeId` | `EmployeeIdValueObject` |
| `startedAt` | `DateTimeImmutable` |
| `endedAt` | `?DateTimeImmutable` |
| `status` | `EmploymentPeriodStatus` (ACTIVE / CLOSED) |

**Factory:** `EmploymentPeriod::start(...)`. **Ciclo:** crear ACTIVE al contratar (`CreateEmployeeHandler`), `close()` al terminar (`DeactivateEmployeeHandler`), nuevo al reactivar (`ReactivateEmployeeHandler`). ✅ G-07 resuelto.

### 3.7 ServiceZoneAssignment (historial de asignaciones de técnicos)

| Campo | Tipo |
|-------|------|
| `id` | `ServiceZoneAssignmentIdValueObject` (ULID) |
| `employeeId` | `EmployeeIdValueObject` (técnico asignado) |
| `newZoneId` | `ServiceZoneIdValueObject` (nueva zona) |
| `previousZoneId` | `?ServiceZoneIdValueObject` (zona anterior; null si es primera asignación) |
| `assignedByUserId` | `?string` (ULID del User que realizó la asignación) |

**Factory:** `ServiceZoneAssignment::create(...)`. Registro de historial (no se elimina); cada asignación/reasignación crea una fila nueva (C-14 `AssignTechnicianToZone`). Las ciudades de zona NO viven aquí — están en `ServiceZone.cityIds` (3.2).

---

## 4. Commands

> Verificado en disco: **33 commands** en `src/app/Organization/Application/Command/` (32 documentados + `UnlinkUserFromEmployee` que faltaba).

| ID | Command | Handler | Caller real (wiring) |
|----|---------|---------|----------------------|
| C‑01 | `CreateEmployee` | Crea empleado | `ApiOrganizationController.php:99`, `EmployeeController.php:97`, `ImportEmployeesHandler.php:65` |
| C‑02 | `UpdateEmployee` | Actualiza datos | `ApiOrganizationController.php:147`, `EmployeeController.php:235` |
| C‑03 | `DeactivateEmployee` | Termina + cierra EmploymentPeriod + verifica blockers | `ApiOrganizationController.php:171`, `EmployeeController.php:340` |
| C‑04 | `ReactivateEmployee` | Reactiva | `ApiOrganizationController.php:193`, `EmployeeController.php:356` |
| C‑05 | `SoftDeleteEmployee` | Soft delete | `ApiOrganizationController.php:210`, `EmployeeController.php:372` |
| C‑06 | `LinkUserToEmployee` | Vincula User de Identity | `EmployeeController.php:184` |
| C‑07 | `ProvisionEmployeeAccess` | Crea User en Identity + vincula | `ApiOrganizationController.php:621`, `EmployeeController.php:299` |
| C‑08 | `ImportEmployees` | Importa CSV (sync; dry-run) | `ApiOrganizationImportController.php:38`, `OrganizationImportController.php:50`, `OrganizationImportEmployeesCliCommand.php:43` |
| C‑09 | `CreateServiceZone` | Crea zona | `ApiServiceZoneController.php:50`, `ServiceZoneController.php:71` |
| C‑10 | `UpdateServiceZone` | Actualiza zona | `ApiServiceZoneController.php:77`, `ServiceZoneController.php:136` |
| C‑11 | `DeactivateServiceZone` | Desactiva zona | `ApiServiceZoneController.php:107`, `ServiceZoneController.php:169` |
| C‑12 | `ReactivateServiceZone` | Reactiva zona | `ApiServiceZoneController.php:122`, `ServiceZoneController.php:187` |
| C‑13 | `DeleteServiceZone` | Soft delete | `ApiServiceZoneController.php:92` |
| C‑14 | `AssignTechnicianToZone` | Asigna técnico + ServiceZoneAssignment | `ServiceZoneController.php:208` |
| C‑15 | `RemoveTechnicianFromZone` | Remueve técnico | `ServiceZoneController.php:227` |
| C‑16 | `AddCityToZone` | Agrega ciudad | `ServiceZoneController.php:246` |
| C‑17 | `RemoveCityFromZone` | Remueve ciudad | `ServiceZoneController.php:265` |
| C‑18 | `TransferCity` | Transfiere ciudad entre zonas | `ApiServiceZoneController.php:139` |
| C‑19 | `CreateVacationRequest` | Crea solicitud | `ApiOrganizationController.php:344` |
| C‑20 | `ApproveVacationRequest` | Aprueba + crea EmployeeSpecialPeriod VACATION | `ApiOrganizationController.php:387` |
| C‑21 | `RejectVacationRequest` | Rechaza con motivo | `ApiOrganizationController.php:417` |
| C‑22 | `CancelVacationRequest` | Cancela solicitud | `ApiOrganizationController.php:447` |
| C‑23 | `RegisterOvertime` | Registra hora extra | `ApiOrganizationController.php:468` |
| C‑24 | `ApproveOvertime` | Aprueba hora extra | `ApiOrganizationController.php:514` |
| C‑25 | `RejectOvertime` | Rechaza hora extra | `ApiOrganizationController.php:544` |
| C‑26 | `RequestOvertimeCorrection` | Solicita corrección | `ApiOrganizationController.php:574` |
| C‑27 | `CorrectOvertime` | Corrige hora extra | `ApiOrganizationController.php:596` |
| C‑28 | `ScheduleEmployeeVacation` | Programa VACATION | `ApiOrganizationController.php:251`, `EmployeeController.php:393` |
| C‑29 | `ScheduleEmployeeStandby` | Programa STANDBY | `ApiOrganizationController.php:257`, `EmployeeController.php:413` |
| C‑30 | `CompleteEmployeeSpecialPeriod` | Completa manualmente | `ApiOrganizationController.php:320` |
| C‑31 | `CancelEmployeeSpecialPeriod` | Cancela período | `EmployeeController.php:429` |
| C‑32 | `EditEmployeeSpecialPeriod` | Edita período | `ApiOrganizationController.php:292` |
| C‑33 | `UnlinkUserFromEmployee` | Desvincula User | `ApiOrganizationController.php:643`, `EmployeeController.php:206` |

---

## 5. Queries

> Verificado en disco: **8 queries** en `src/app/Organization/Application/Query/`.

| ID | Query | Response | Caller real (wiring) |
|----|-------|----------|----------------------|
| Q‑01 | `ListEmployees` | `EmployeeResponse[]` | `ApiOrganizationController.php:78`, `EmployeeController.php:65`, `ServiceZoneController.php:100`, `Support/TicketController.php:140`, `Inventory/CustodyController.php:44,65,86,104,145`, `Expense/ExpenseController.php:141,173,401,431` |
| Q‑02 | `FindEmployeeById` | `EmployeeResponse` | `ApiOrganizationController.php:123`, `EmployeeController.php` (show) |
| Q‑03 | `ListServiceZones` | `ServiceZoneResponse[]` | `ApiServiceZoneController.php:39`, `ServiceZoneController.php:46` |
| Q‑04 | `FindServiceZoneById` | `ServiceZoneResponse` | `ApiServiceZoneController.php:63`, `ServiceZoneController.php` (show) |
| Q‑05 | `ListOvertimeRecordsByEmployee` | `OvertimeRecordResponse[]` | `ApiOrganizationController.php:492` |
| Q‑06 | `ListVacationRequestsByEmployee` | `VacationRequestResponse[]` | `ApiOrganizationController.php:365` |
| Q‑07 | `ListEmployeeSpecialPeriods` | `EmployeeSpecialPeriodResponse[]` | `ApiOrganizationController.php:124, 227` |
| Q‑08 | `GetOrganizationDashboard` | KPIs summary (array) | `ApiOrganizationController.php:661`, `OrganizationDashboardController.php:28` |

**Q‑01 ListEmployeesQuery** (firma real): `profile: ?EmployeeProfile`, `status: ?EmployeeStatus`, `search: ?string`.
**⚠️ Bug latente:** `ApiOrganizationController.php:78` le pasa `serviceZoneId`, `q`, `page`, `limit` (parametros que NO existen) y `CustodyController`/`ExpenseController` pasan `limit: 500` → PHP lanza `Error: Unknown named parameter` en runtime (ver G-06).

---

## 6. Cross-BC Interactions

> Verificado en disco: publicación vía `SymfonyDomainEventPublisher` → `DomainEventBridgeSubscriber` → Messenger **async** (`DomainEventMessage` → transport `async_events`). No existe camino sync para la cascada.

| BC | Tipo | Interacción | Consumidor real |
|----|------|-------------|-----------------|
| **Identity** | Escritura | `ProvisionEmployeeAccess` crea User en Identity | `ProvisionEmployeeAccessHandler` (+ `ProvisionEmployeeAccessHandlerInterface`) |
| **Identity** | Escritura | Cascada Employee→User (deactivate/delete/reactivate) | `Organization/Infrastructure/Messenger/Handler/EmployeeStatusCascadeHandler.php` (async, escucha `EmployeeDeactivated`, `EmployeeDeleted`, `EmployeeReactivated`) |
| **Identity** | Lectura | Invariante de vinculación 1:1 (spec 010a) | `EmployeeUserInvariantService` + `UserBindingCheckerInterface` |
| **Support** | Lectura | `TerminationVerificationService` consulta tickets abiertos (bloqueo de terminación) | `TerminationVerificationService.php` (usa `TicketRepositoryInterface`) |
| **Inventory** | Escritura | `EmployeeCreated` → auto-crea mochila de técnico | `Inventory/Infrastructure/Messenger/Handler/AutoCreateBackpackHandler.php` |
| **Expense** | Escritura | `EmployeeDeactivated` → gestión de gastos del empleado | `Expense/Infrastructure/Messenger/Handler/EmployeeDeactivatedHandler.php` |
| **Notification** | Escritura | 24 eventos de Organization → notificaciones | `Notification/Infrastructure/Messenger/Handler/CrossBcDomainEventHandler.php` (CityTransferred, EmployeeAccessProvisioned, EmployeeCreated, EmployeeDeactivated, EmployeeDeleted, EmployeeReactivated, EmployeeUserLinked, EmployeeUserUnlinked, Overtime* ×5, ServiceZoneCreated/Deactivated/Reactivated, SpecialPeriod* ×5, Vacation* ×4) |
| **Location** | Lectura | `cityId` (ULID) en ServiceZoneAssignment referencia Location | Solo IDs entre BCs |
| **Client** | Lectura | `serviceZoneId` en Site (Client BC) referencia Organization | — |

---

## 7. API Endpoints

### API REST (`/api/v1/organization` — `ApiOrganizationController`)

| Método | Ruta | Permiso |
|--------|------|---------|
| `GET` | `/employees` | `employee.view` |
| `POST` | `/employees` | `employee.create` |
| `GET` | `/employees/{id}` | `employee.view` |
| `PATCH` | `/employees/{id}` | `employee.update` |
| `POST` | `/employees/{id}/terminate` | `employee.manage` |
| `POST` | `/employees/{id}/reactivate` | `employee.update` |
| `DELETE` | `/employees/{id}` | `employee.delete` |
| `GET` | `/employees/{id}/special-periods` | `employee.view` |
| `POST` | `/employees/{id}/special-periods` | `employee.update` |
| `PATCH` | `/employees/{id}/special-periods/{periodId}` | `employee.update` |
| `POST` | `/employees/{id}/special-periods/{periodId}/complete` | `employee.update` |
| `GET` | `/employees/{id}/vacation-requests` | `employee.view` |
| `POST` | `/employees/{id}/vacation-requests` | `employee.create` |
| `POST` | `/vacation-requests/{id}/approve` | `employee.update` |
| `POST` | `/vacation-requests/{id}/reject` | `employee.update` |
| `POST` | `/vacation-requests/{id}/cancel` | `employee.update` |
| `GET` | `/employees/{id}/overtime` | `employee.view` |
| `POST` | `/employees/{id}/overtime` | `employee.create` |
| `POST` | `/overtime-records/{id}/approve` | `employee.update` |
| `POST` | `/overtime-records/{id}/reject` | `employee.update` |
| `POST` | `/overtime-records/{id}/request-correction` | `employee.update` |
| `POST` | `/overtime-records/{id}/correct` | `employee.create` |
| `POST` | `/employees/{id}/provision-access` | `employee.manage` |
| `POST` | `/employees/{id}/unlink-user` | `employee.update` |
| `GET` | `/dashboard/summary` | `employee.view` |

### API Service Zones (`/api/v1/organization/service-zones` — `ApiServiceZoneController`)

| Método | Ruta | Permiso |
|--------|------|---------|
| `GET` | `/service-zones` | `service_zone.view_all` |
| `POST` | `/service-zones` | `service_zone.create` |
| `GET` | `/service-zones/{id}` | `service_zone.view_all` |
| `PATCH` | `/service-zones/{id}` | `service_zone.update` |
| `DELETE` | `/service-zones/{id}` | `service_zone.update` |
| `POST` | `/service-zones/{id}/deactivate` | `service_zone.update` |
| `POST` | `/service-zones/{id}/reactivate` | `service_zone.update` |
| `POST` | `/service-zones/{id}/transfer-city` | `service_zone.update` |

### Import API (`ApiOrganizationImportController`)

| Método | Ruta | Permiso |
|--------|------|---------|
| `POST` | `/organization/import/employees` | `organization.import` |
| `GET` | `/organization/import/employees/template` | público (plantilla CSV) |

### Web (controllers Twig)

| Controller | Rutas |
|------------|-------|
| `EmployeeController` (`/employees`) | list, new, create, show, link-user, unlink-user, edit, create-user, deactivate, reactivate, delete, vacation/schedule, standby/schedule, periods/{periodId}/cancel |
| `ServiceZoneController` (`/service-zones`) | list, new, create, show, edit, deactivate, reactivate, assign-technician, remove-technician/{technicianId}, add-city, remove-city/{cityId} |
| `OrganizationDashboardController` (`/organization/dashboard`) | index (KPIs web) |
| `OrganizationImportController` (`/organization/import`) | index (form), employees (POST web) |
| `OrganizationReportController` (`/organization/report`) | `active-personnel.{xlsx\|pdf}`, `scheduled-vacations.{xlsx\|pdf}`, `overtime.xlsx` — permiso `organization.report` |

### Templates existentes (verificados en disco)

`templates/employee/{list,show,form}.html.twig`, `templates/service_zone/{list,show,form}.html.twig`, `templates/organization/import.html.twig`, `templates/organization/dashboard/index.html.twig` (8 templates).

---

## 8. Console Commands

> Verificado en disco: `src/app/Organization/Infrastructure/Console/`.

| Comando | Clase | Propósito |
|---------|-------|-----------|
| `org:process-special-periods` | `ProcessEmployeeSpecialPeriodsCommand` | Auto-procesa SCHEDULED→IN_PROGRESS→COMPLETED (VACATION cambia status del empleado; STANDBY no) |
| `organization:import:employees` | `OrganizationImportEmployeesCliCommand` | Importa empleados CSV (arg `file`, opt `--dry-run`) |
| `seed:dev` | `SeedDevCommand` | Datos de desarrollo |
| `seed:demo` | `SeedDemoDataCommand` | Datos demo |
| `seed:full` | `SeedFullDataCommand` | Dataset completo |

---

## 9. Estado real por componente (verificado 2026-08-07)

| Componente | Estado | Detalle |
|------------|--------|---------|
| **Domain — Entities** | ✅ Implementado | 7 entidades (Employee, ServiceZone, VacationRequest, OvertimeRecord, EmployeeSpecialPeriod, EmploymentPeriod, ServiceZoneAssignment) |
| **Domain — ValueObjects** | ✅ Implementado | 10 VOs |
| **Domain — Enums** | ✅ Implementado | 8 enums string-backed (custom DBAL types) |
| **Domain — Events** | ✅ Implementado | 25 eventos; todos publican desde handlers/consores (`SpecialPeriodAutoProcessed` → `org:process-special-periods` cron) |
| **Domain — Exceptions** | ✅ Implementado | 23 excepciones de dominio (verificadas) + 6 de aplicación |
| **Domain — Repositories** | ✅ Implementado | 7 interfaces |
| **Application — Commands** | ✅ Implementado | 33 commands + handlers, todos con caller real (wiring-check 0 huérfanos) |
| **Application — Queries** | ⚠️ Parcial | 8 queries; Q‑01 con bug de parámetros fantasma (G-06) |
| **Application — Services** | ✅ Implementado | `TerminationVerificationService` (+interfaz), `EmployeeUserInvariantService` |
| **Application — Reportes** | ✅ Implementado | 3 generadores (ActivePersonnel, Overtime, ScheduledVacations) — DBAL directo, `ReportGeneratorInterface` |
| **Infrastructure — Persistence** | ✅ Implementado | 7 Doctrine repos + 7 ORM mappings XML + 17 DBAL types (verificado en `doctrine.yaml` + disco) |
| **Infrastructure — Import** | ✅ Implementado | `EmployeeCsvFileParser`, `EmployeeRowValidator`, handler sync |
| **Infrastructure — Messenger** | ✅ Implementado | `EmployeeStatusCascadeHandler` (async) |
| **Infrastructure — Console** | ✅ Implementado | 5 comandos |
| **Presentation — API** | ✅ Implementado | 3 controllers API (employees/zones/import) |
| **Presentation — Web** | ✅ Implementado | 5 controllers web + 8 templates |
| **Dashboard** | ✅ Implementado | Web (`OrganizationDashboardController`) + API (`/dashboard/summary`); 7 KPIs de analytics.md F.1.2 Organization todos cubiertos |
| **Reportes exportables** | ✅ Implementado | xlsx (3) + pdf (2: ActivePersonnel, ScheduledVacations) |
| **Tests** | ✅ Implementado | 60 archivos válidos (3 basura `*-DESKTOP-GU1L412.php` eliminados — G-09 resuelto) |
| **Wiring** | ✅ OK | `php bin/wiring-check.php`: 346 targets, 0 huérfanos (exit 0) |

---

## 10. Wiring completo (verificado con grep/CodeGraph 2026-08-07)

### 10.1 Commands → callers

| Command | Caller(s) reales |
|---------|------------------|
| CreateEmployee | ApiOrganizationController:99 · EmployeeController:97 · ImportEmployeesHandler:65 |
| UpdateEmployee | ApiOrganizationController:147 · EmployeeController:235 |
| DeactivateEmployee | ApiOrganizationController:171 · EmployeeController:340 |
| ReactivateEmployee | ApiOrganizationController:193 · EmployeeController:356 |
| SoftDeleteEmployee | ApiOrganizationController:210 · EmployeeController:372 |
| LinkUserToEmployee | EmployeeController:184 |
| ProvisionEmployeeAccess | ApiOrganizationController:621 · EmployeeController:299 |
| ImportEmployees | ApiOrganizationImportController:38 · OrganizationImportController:50 · OrganizationImportEmployeesCliCommand:43 |
| CreateServiceZone | ApiServiceZoneController:50 · ServiceZoneController:71 |
| UpdateServiceZone | ApiServiceZoneController:77 · ServiceZoneController:136 |
| DeactivateServiceZone | ApiServiceZoneController:107 · ServiceZoneController:169 |
| ReactivateServiceZone | ApiServiceZoneController:122 · ServiceZoneController:187 |
| DeleteServiceZone | ApiServiceZoneController:92 |
| AssignTechnicianToZone | ServiceZoneController:208 |
| RemoveTechnicianFromZone | ServiceZoneController:227 |
| AddCityToZone | ServiceZoneController:246 |
| RemoveCityFromZone | ServiceZoneController:265 |
| TransferCity | ApiServiceZoneController:139 |
| CreateVacationRequest | ApiOrganizationController:344 |
| ApproveVacationRequest | ApiOrganizationController:387 |
| RejectVacationRequest | ApiOrganizationController:417 |
| CancelVacationRequest | ApiOrganizationController:447 |
| RegisterOvertime | ApiOrganizationController:468 |
| ApproveOvertime | ApiOrganizationController:514 |
| RejectOvertime | ApiOrganizationController:544 |
| RequestOvertimeCorrection | ApiOrganizationController:574 |
| CorrectOvertime | ApiOrganizationController:596 |
| ScheduleEmployeeVacation | ApiOrganizationController:251 · EmployeeController:393 |
| ScheduleEmployeeStandby | ApiOrganizationController:257 · EmployeeController:413 |
| CompleteEmployeeSpecialPeriod | ApiOrganizationController:320 |
| CancelEmployeeSpecialPeriod | EmployeeController:429 |
| EditEmployeeSpecialPeriod | ApiOrganizationController:292 |
| UnlinkUserFromEmployee | ApiOrganizationController:643 · EmployeeController:206 |

### 10.2 Queries → callers

| Query | Caller(s) reales |
|-------|------------------|
| ListEmployees | ApiOrganizationController:78 · EmployeeController:65 · ServiceZoneController:100 · Support TicketController:140 · Inventory CustodyController:44,65,86,104,145 · Expense ExpenseController:141,173,401,431 |
| FindEmployeeById | ApiOrganizationController:123 · EmployeeController (show) |
| ListServiceZones | ApiServiceZoneController:39 · ServiceZoneController:46 |
| FindServiceZoneById | ApiServiceZoneController:63 · ServiceZoneController (show) |
| ListOvertimeRecordsByEmployee | ApiOrganizationController:492 |
| ListVacationRequestsByEmployee | ApiOrganizationController:365 |
| ListEmployeeSpecialPeriods | ApiOrganizationController:124, 227 |
| GetOrganizationDashboard | ApiOrganizationController:661 · OrganizationDashboardController:28 |

### 10.3 Domain Events → publicadores / consumidores

| Evento | Publicado por | Consumido por |
|--------|---------------|---------------|
| EmployeeCreated | CreateEmployeeHandler:46 | Inventory AutoCreateBackpackHandler · Notification CrossBcDomainEventHandler |
| EmployeeDeactivated | DeactivateEmployeeHandler | Org EmployeeStatusCascadeHandler · Expense EmployeeDeactivatedHandler · Notification |
| EmployeeDeleted | SoftDeleteEmployeeHandler | Org EmployeeStatusCascadeHandler · Notification |
| EmployeeReactivated | ReactivateEmployeeHandler | Org EmployeeStatusCascadeHandler · Notification |
| EmployeeUserLinked | LinkUserToEmployeeHandler | Notification |
| EmployeeUserUnlinked | UnlinkUserFromEmployeeHandler | Notification |
| EmployeeAccessProvisioned | ProvisionEmployeeAccessHandler | Notification |
| VacationRequested | CreateVacationRequestHandler | Notification |
| VacationApproved | ApproveVacationRequestHandler | Notification |
| VacationRejected | RejectVacationRequestHandler | Notification |
| VacationCancelled | CancelVacationRequestHandler | Notification |
| OvertimeRegistered / Approved / Rejected / ReviewRequested / Corrected | OvertimeRecord entity (pullDomainEvents) + handlers | Notification |
| ServiceZoneCreated / Deactivated / Reactivated | handlers de ServiceZone | Notification |
| SpecialPeriodScheduled | ScheduleEmployeeVacation/StandbyHandlers | Notification |
| SpecialPeriodCompleted | CompleteEmployeeSpecialPeriodHandler | Notification |
| SpecialPeriodCancelled | CancelEmployeeSpecialPeriodHandler | Notification |
| SpecialPeriodEdited | EditEmployeeSpecialPeriodHandler | Notification |
| CityTransferred | TransferCityHandler | Notification |
| **SpecialPeriodAutoProcessed** | `org:process-special-periods` (cron) | Notification |

**Transporte:** `SymfonyDomainEventPublisher` → EventDispatcher → `DomainEventBridgeSubscriber` → `DomainEventMessage` → Messenger `async_events`. El proceso cron `org:process-special-periods` NO publica eventos.

### 10.4 Reportes → endpoints

| Reporte | Endpoint | Formato |
|---------|----------|---------|
| ActivePersonnelReportGenerator | `GET /organization/report/active-personnel.{_format}` | xlsx + pdf |
| ScheduledVacationsReportGenerator | `GET /organization/report/scheduled-vacations.{_format}` | xlsx + pdf |
| OvertimeReportGenerator | `GET /organization/report/overtime.{_format}` | xlsx |

Export vía `ExcelExportService`/`PdfExportService` (Shared Kernel). Permiso `organization.report`.

---

## 11. Known Gaps (verificados 2026-08-07)

| # | Gap | Prioridad | Estado |
|---|-----|-----------|--------|
| G‑01 | ~~EmploymentPeriod, OvertimeRecord, VacationRequest sin ORM mappings~~ | — | ✅ **OBSOLETO — los 7 mappings XML existen** (`Infrastructure/Persistence/Doctrine/Mapping/`) |
| G‑02 | ~~`organization/import.html.twig` no existe~~ | — | ✅ **OBSOLETO — el template SÍ existe en disco** |
| G‑03 | ~~Dual cascade path (sync listener + async Messenger)~~ | — | ✅ **OBSOLETO — solo existe el camino async** (DomainEventBridgeSubscriber → async_events) |
| G‑04 | ~~ListEmployeesQuery vacío~~ | — | ⚠️ **PARCIAL — ya tiene** `profile/status/search`, pero ver G-06 |
| G‑05 | ~~Tests: 30 archivos, faltan handlers~~ | — | ⚠️ **OBSOLETO — hay 60 tests válidos + 3 basura** (casi todos los handlers cubiertos) |
| G‑06 | **Bug latente `ListEmployeesQuery`**: `ApiOrganizationController:78` pasa `serviceZoneId/q/page/limit`; `CustodyController` y `ExpenseController` pasan `limit: 500` — ninguno existe en el query → `Error: Unknown named parameter` en runtime | **Crítica** | ⬜ Abierto |
| G‑07 | **EmploymentPeriod nunca se crea**: solo `close()` en DeactivateEmployeeHandler; ninguna ruta instancia `EmploymentPeriod::start()` al contratar/reactivar → historial de altas vacío | Alta | ⬜ Abierto |
| G‑08 | **`SpecialPeriodAutoProcessed` sin emisor**: evento definido pero `org:process-special-periods` no publica eventos | Media | ⬜ Abierto |
| G‑09 | **3 archivos de test duplicados basura** `*-DESKTOP-GU1L412.php` (copias con sufijo de hostname) — 2 en `tests/Unit/Organization/` (`EmployeeIdentificationNumberValueObjectTest`, `EmployeeTest`) y 1 en `tests/Unit/Inventory/Application/Query/` (`StockOverviewHandlerTest`) — PHPUnit los ejecuta | Media | ⬜ Abierto |
| G‑10 | **Import sync-only**: spec C-08 prevé async >100 filas; no existe `ImportEmployeesMessage`/Messenger handler | Media | ⬜ Abierto |
| G‑11 | **Import sin cross-BC**: no vincula `serviceZoneId` ni `userId` (analytics.md F.3.5 lo prevé) | Baja | ⬜ Abierto |
| G‑12 | **Prefijos de tabla inconsistentes**: `employees`, `service_zones`, `employee_special_periods` sin prefijo vs `org_overtime_records`, `org_vacation_requests`, `org_employment_periods`, `org_service_zone_assignments` con `org_` (análogo ADR-013 de Inventory) | Baja | ⬜ Abierto |
| G‑13 | **Labels de perfil desactualizados en reportes**: `ActivePersonnel`/`ScheduledVacations` usan 5 labels históricos (`technician_field`, `supervisor_technician`, `warehouse_worker`, `office_staff`, `admin`) pero `EmployeeProfile` enum real solo tiene 3 (`technician`, `coordinator`, `administrative`) | Baja | ⬜ Abierto |
| G‑14 | **Exposición asimétrica**: `DeleteServiceZone`/`TransferCity`/`Complete|Edit|CancelEmployeeSpecialPeriod` solo API o solo web (sin duplicar UI) | Baja | ⬜ Abierto |

### Camino al 95% (pendientes reales)

1. **G-06 (crítico):** corregir firma de `ListEmployeesQuery` (agregar `serviceZoneId`, `page`, `limit` y paginación real) o ajustar callers — desbloquea list API y cross-BC (Custody, Expense, Support).
2. **G-07:** ✅ crear `EmploymentPeriod::start()` en `CreateEmployeeHandler` y `ReactivateEmployeeHandler`.
3. **G-08:** ✅ publicar `SpecialPeriodAutoProcessed` desde `ProcessEmployeeSpecialPeriodsCommand`.
4. **G-09:** ✅ eliminar los 3 archivos basura de tests (2 en `tests/Unit/Organization/`, 1 en `tests/Unit/Inventory/Application/Query/`).
5. **G-10/G-11:** variante async del import (>100 filas) y validación/vinculación cross-BC.
6. **G-12/G-13:** unificar prefijos de tablas y labels de perfil (deuda de naming).

---

## 12. Documentos relacionados

- `specs/010a-employee-user-invariant/spec.md` — invariante de vinculación Employee↔User (REQ-CST-001)
- `docs/analytics.md` §F.1.2 (Organization KPIs — todos cubiertos por `GetOrganizationDashboardHandler`), §F.2.2 (reportes P1/P2 — completos)
- `docs/modules/organization.md` — módulo del BC
- `CONTEXT.md` §2/§3/§4 — tabla maestra de estado y reportes verificados
