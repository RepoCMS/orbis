# Feature Specification: Organization Management

**Feature Branch**: `001-organization-spec`

**Created**: 2026-06-17

**Status**: In Development (~67% implementado)

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

---

## 2. Business Rules

| # | Regla | Descripción |
|---|-------|-------------|
| OR‑01 | **Identificación única** | No pueden existir dos empleados con la misma identificación. |
| OR‑02 | **Email único** | El email corporativo debe ser único por empleado. |
| OR‑03 | **Ciclo de vida empleado** | ACTIVE → INACTIVE (al terminar). ON_VACATION se actualiza automáticamente. |
| OR‑04 | **Vinculación única con User** | Un empleado puede vincularse a un User de Identity BC (1:1). |
| OR‑05 | **Perfil solo descriptivo** | El profile (technician/coordinator/administrative) es informativo, no autorizativo. La autorización va por Role en Identity BC. |
| OR‑06 | **Zona de servicio** | Un empleado puede estar asignado a una sola zona. Múltiples técnicos por zona. |
| OR‑07 | **Vacaciones** | Solicitud → Aprobación → Período especial VACATION → ON_VACATION. Rechazo mantiene ACTIVE. |
| OR‑08 | **Standby** | Período de trabajo en feriado/fin de semana. NO cambia el status del empleado. |
| OR‑09 | **Horas extras** | Registro → Revisión → Aprobación/Rechazo. Puede solicitar corrección. |
| OR‑10 | **Terminación bloqueada** | Un empleado con tickets abiertos o períodos especiales activos no puede ser terminado. |
| OR‑11 | **Procesamiento automático** | `ProcessEmployeeSpecialPeriodsCommand` auto-procesa transiciones SCHEDULED→IN_PROGRESS→COMPLETED. |

---

## 3. Aggregates

### 3.1 Employee (Aggregate Root)

| Campo | Tipo |
|-------|------|
| `id` | `EmployeeIdValueObject` (ULID) |
| `userId` | `?UserIdValueObject` (Identity BC) |
| `identificationNumber` | `EmployeeIdentificationNumberValueObject` |
| `firstName`, `lastName` | `string` (5-100 chars) |
| `email` | `EmployeeEmailValueObject` |
| `phoneNumber` | `string` (09XXXXXXXX, 10 dígitos) |
| `status` | `EmployeeStatus` (ACTIVE / ON_VACATION / INACTIVE) |
| `profile` | `EmployeeProfile` (technician / coordinator / administrative) |
| `serviceZoneId` | `?ServiceZoneIdValueObject` |
| `hiredAt` | `DateTimeImmutable` |
| `terminatedAt` | `?DateTimeImmutable` |
| `deletedAt` | `?DateTimeImmutable` |

**Factory:** `Employee::hire(id, identificationNumber, firstName, lastName, email, phoneNumber, profile): self`

**State transitions:** `linkUser()`, `unlinkUser()`, `assignToZone()`, `removeFromZone()`, `terminate()` (INACTIVE), `reactivate()` (ACTIVE), `startVacation()`, `returnFromVacation()`, `softDelete()`

### 3.2 ServiceZone

| Campo | Tipo |
|-------|------|
| `id` | `ServiceZoneIdValueObject` (ULID) |
| `name` | `string` (3-100 chars) |
| `status` | `ServiceZoneStatus` (ACTIVE / INACTIVE) |
| `cityIds` | `string[]` (ULIDs de Location BC) |

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

**Factory:** `VacationRequest::request(...)`. Al aprobarse se crea un `EmployeeSpecialPeriod` tipo VACATION.

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

**Status machine:** PENDING → IN_REVIEW → APPROVED/REJECTED. IN_REVIEW → PENDING (via correct).

### 3.5 EmployeeSpecialPeriod

| Campo | Tipo |
|-------|------|
| `id` | `EmployeeSpecialPeriodIdValueObject` (ULID) |
| `employeeId` | `EmployeeIdValueObject` |
| `type` | `EmployeeSpecialPeriodType` (VACATION / STANDBY) |
| `status` | `EmployeeSpecialPeriodStatus` (SCHEDULED / IN_PROGRESS / COMPLETED / CANCELLED) |

**Status machine:** SCHEDULED → IN_PROGRESS → COMPLETED. Auto-procesado por comando cron. Cualquier estado → CANCELLED (manual).

---

## 4. Commands

| ID | Command | Handler |
|----|---------|---------|
| C‑01 | `CreateEmployee` | Crea empleado + EmploymentPeriod activo |
| C‑02 | `UpdateEmployee` | Actualiza datos del empleado |
| C‑03 | `DeactivateEmployee` | Termina empleado + cierra EmploymentPeriod + verifica blockers |
| C‑04 | `ReactivateEmployee` | Reactiva + nuevo EmploymentPeriod |
| C‑05 | `SoftDeleteEmployee` | Soft delete |
| C‑06 | `LinkUserToEmployee` | Vincula User de Identity |
| C‑07 | `ProvisionEmployeeAccess` | Crea User en Identity + vincula |
| C‑08 | `ImportEmployees` | Importa desde CSV (sync ≤100, async >100) |
| C‑09 | `CreateServiceZone` | Crea zona de servicio |
| C‑10 | `UpdateServiceZone` | Actualiza zona |
| C‑11 | `DeactivateServiceZone` | Desactiva zona |
| C‑12 | `ReactivateServiceZone` | Reactiva zona |
| C‑13 | `DeleteServiceZone` | Soft delete |
| C‑14 | `AssignTechnicianToZone` | Asigna técnico + crea ServiceZoneAssignment |
| C‑15 | `RemoveTechnicianFromZone` | Remueve técnico de zona |
| C‑16 | `AddCityToZone` | Agrega ciudad a zona |
| C‑17 | `RemoveCityFromZone` | Remueve ciudad de zona |
| C‑18 | `TransferCity` | Transfiere ciudad entre zonas |
| C‑19 | `CreateVacationRequest` | Crea solicitud de vacaciones |
| C‑20 | `ApproveVacationRequest` | Aprueba + crea EmployeeSpecialPeriod VACATION |
| C‑21 | `RejectVacationRequest` | Rechaza con motivo |
| C‑22 | `CancelVacationRequest` | Cancela solicitud |
| C‑23 | `RegisterOvertime` | Registra hora extra |
| C‑24 | `ApproveOvertime` | Aprueba hora extra |
| C‑25 | `RejectOvertime` | Rechaza hora extra |
| C‑26 | `RequestOvertimeCorrection` | Solicita corrección |
| C‑27 | `CorrectOvertime` | Corrige hora extra |
| C‑28 | `ScheduleEmployeeVacation` | Programa período VACATION directamente |
| C‑29 | `ScheduleEmployeeStandby` | Programa período STANDBY |
| C‑30 | `CompleteEmployeeSpecialPeriod` | Completa período manualmente |
| C‑31 | `CancelEmployeeSpecialPeriod` | Cancela período |
| C‑32 | `EditEmployeeSpecialPeriod` | Edita período |

---

## 5. Queries

| ID | Query | Response |
|----|-------|----------|
| Q‑01 | `ListEmployees` | `EmployeeResponse[]` |
| Q‑02 | `FindEmployeeById` | `EmployeeResponse` |
| Q‑03 | `ListServiceZones` | `ServiceZoneResponse[]` |
| Q‑04 | `FindServiceZoneById` | `ServiceZoneResponse` |
| Q‑05 | `ListOvertimeRecordsByEmployee` | `OvertimeRecordResponse[]` |
| Q‑06 | `ListVacationRequestsByEmployee` | `VacationRequestResponse[]` |
| Q‑07 | `ListEmployeeSpecialPeriods` | `EmployeeSpecialPeriodResponse[]` |
| Q‑08 | `GetOrganizationDashboard` | KPIs summary |

---

## 6. Cross-BC Interactions

| BC | Tipo | Interacción |
|----|------|-------------|
| **Identity** | Escritura | `ProvisionEmployeeAccess` crea User en Identity |
| **Identity** | Escritura | `EmployeeStatusCascadeHandler` desactiva/elimina/reactiva User vinculado |
| **Location** | Lectura | `cityIds` en ServiceZone referencia Location BC |
| **Support** | Lectura | `TerminationVerificationService` consulta tickets abiertos |
| **Client** | Lectura | `serviceZoneId` en Site referencia Organization |

---

## 7. API Endpoints

### API REST (`/api/v1/organization/`)

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
| `GET` | `/employees/{id}/vacation-requests` | `employee.view` |
| `POST` | `/employees/{id}/vacation-requests` | `employee.create` |
| `POST` | `/vacation-requests/{id}/approve` | `employee.update` |
| `POST` | `/vacation-requests/{id}/reject` | `employee.update` |
| `POST` | `/employees/{id}/overtime` | `employee.create` |
| `GET` | `/employees/{id}/overtime` | `employee.view` |
| `POST` | `/overtime-records/{id}/approve` | `employee.update` |
| `POST` | `/employees/{id}/provision-access` | `employee.manage` |
| `GET` | `/dashboard/summary` | `employee.view` |
| `GET` | `/service-zones` | `service_zone.view_all` |
| `POST` | `/service-zones` | `service_zone.create` |
| `GET` | `/service-zones/{id}` | `service_zone.view_all` |
| `PATCH` | `/service-zones/{id}` | `service_zone.update` |
| `DELETE` | `/service-zones/{id}` | `service_zone.update` |

---

## 8. Console Commands

| Comando | Propósito |
|---------|-----------|
| `process:employee-special-periods` | Auto-procesa transiciones de EmployeeSpecialPeriod |
| `seed:dev` | Datos de desarrollo |
| `seed:demo` | Datos demo |
| `seed:full` | Dataset completo |

---

## 9. Known Gaps

| # | Gap | Prioridad |
|---|-----|-----------|
| G‑01 | `EmploymentPeriod`, `OvertimeRecord`, `VacationRequest` sin ORM mappings XML | Alta |
| G‑02 | `organization/import.html.twig` referenciado pero no existe en disco | Media |
| G‑03 | Dual cascade path: sync listener + async Messenger para Employee→User | Media (unificar) |
| G‑04 | `ListEmployeesQuery` vacío (sin propiedades de filtro a pesar de ser usados en controller) | Media |
| G‑05 | Tests: 30 archivos. Cubren bien entities pero faltan handlers | Media |
