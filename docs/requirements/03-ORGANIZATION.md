# BC Organization — Especificación de Dominio

**Propósito:** Gestión del personal interno de la empresa (empleados, vacaciones, standby, zonas de servicio).

**Estado:** ✅ `Employee` + `EmployeeSpecialPeriod` — Completos. `ServiceZone` — ⚪ Pendiente.

**Dependencias cross-BC:** Identity (usuario asociado al empleado), Location (ciudades de zonas)

---

## Entidades y Aggregates

### Aggregate Root — `Employee`

`Employee` representa un colaborador interno de la empresa. Es dueño de sus datos personales (nombre, email, teléfono).

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| userId | ULID? | Referencia opcional a User (Identity BC) |
| serviceZoneId | ULID? | Zona de servicio asignada (`ServiceZone`) |
| identificationNumber | string | Número de identificación (numérico, máximo 10 dígitos) |
| firstName | string | Nombre (2–100 caracteres, normalizado) |
| lastName | string | Apellido (2–100 caracteres, normalizado) |
| email | string | Email propio del empleado |
| phoneNumber | string | Teléfono ecuatoriano (`09` + 8 dígitos) |
| hiredAt | date | Fecha de contratación |
| terminatedAt | date? | Fecha de baja (`null` si está activo) |
| status | enum | `ACTIVE` / `INACTIVE` |
| deletedAt | datetime? | Soft delete |

> `createdAt` / `updatedAt` gestionados por Doctrine.

#### Value Objects de Employee

| Value Object | Reglas |
|---|---|
| `EmployeeEmailValueObject` | Normalizado a minúsculas, formato email válido |
| `EmployeeIdentificationNumberValueObject` | Numérico, máximo 10 dígitos |

#### Estados del Employee

```
ACTIVE ──── terminate() ────► INACTIVE
  ▲                                │
  └──────── reactivate() ──────────┘
```

#### Comportamientos del Employee

| Método | Guard | Efecto |
|---|---|---|
| `hire()` | status = ACTIVE | registra un nuevo empleado |
| `terminate()` | status = ACTIVE | status → INACTIVE, terminatedAt = hoy |
| `reactivate()` | status = INACTIVE | status → ACTIVE, terminatedAt = null |
| `changeFirstName(name)` | — | firstName actualizado |
| `changeLastName(name)` | — | lastName actualizado |
| `changePhoneNumber(phone)` | — | phoneNumber actualizado |
| `linkUser(userId)` | userId es null actualmente | userId = userId |
| `unlinkUser()` | userId != null | userId = null |
| `softDelete()` | — | deletedAt = now() |

#### Invariantes del Employee

1. Un empleado `ACTIVE` **no puede** tener `terminatedAt`
2. Un empleado `INACTIVE` **debe** tener `terminatedAt`
3. `terminatedAt` no puede ser anterior a `hiredAt`
4. Al terminar/desactivar un empleado con `userId` → su `User` debe pasar a `INACTIVE` (cross-BC via Domain Event)
5. Al reactivar un empleado con `userId` `INACTIVE` → su `User` debe pasar a `ACTIVE` (cross-BC via Domain Event)
6. Al eliminar lógicamente un empleado con `userId` → su `User` debe ser eliminado lógicamente (cross-BC via Domain Event)

#### Reasignación al dar de baja un empleado

Cuando un empleado es desactivado o dado de baja, sus actividades abiertas deben **reasignarse** a otro personal antes de confirmar la baja:

- Tickets de Support asignados al técnico → reasignar a otro técnico
- Reparaciones en laboratorio a cargo del laboratorista → reasignar a otro laboratorista
- Solicitudes de inventario pendientes → reasignar o cancelar

El Application Service de baja verifica existencia de actividades abiertas y las reasigna (o fuerza al operador a confirmar la baja indicando a quién se reasigna).

#### Reasignación al programar VACACIONES (EmployeeSpecialPeriod)

Cuando se registra un período de vacaciones para un técnico que tiene **tickets activos o reparaciones activas**:

- **NO se bloquea** el registro de las vacaciones si `startedAt` es en el futuro.
- El sistema genera una **notificación/alerta al coordinador** indicando que el técnico tiene actividades abiertas que deben reasignarse antes de que inicien las vacaciones.
- Cuando el período pasa a `IN_PROGRESS` (via `ProcessEmployeeSpecialPeriodsCommand`), el empleado pasa a `ON_VACATION` y **no puede recibir nuevas asignaciones** (`isAvailableForAssignment() = false`).
- Los tickets o reparaciones que quedaron sin reasignar al inicio del período se marcan con alerta en el sistema (el coordinador debe gestionarlos).

---

### Entidad — `EmployeeSpecialPeriod`

Período especial del empleado: vacaciones o standby. El administrador los programa directamente (sin flujo de aprobación en el MVP).

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| employeeId | ULID | Referencia al Employee |
| type | enum | `VACATION` / `STANDBY` |
| startedAt | datetime | Inicio del período |
| endedAt | datetime | Fin del período |
| notes | string? | Notas opcionales |
| status | enum | `SCHEDULED` / `IN_PROGRESS` / `COMPLETED` / `CANCELLED` |

#### Estados del EmployeeSpecialPeriod

```
SCHEDULED ──── begin() ────► IN_PROGRESS ──── complete() ────► COMPLETED
     │                             │
     └──── cancel() ───────────────┴──── cancel() ────► CANCELLED
```

#### Comportamientos del EmployeeSpecialPeriod

| Método | Guard | Efecto |
|---|---|---|
| `schedule(id, employeeId, type, startedAt, endedAt, notes?)` | — | Crea en SCHEDULED (o IN_PROGRESS si startedAt ≤ now) |
| `begin()` | status = SCHEDULED | status → IN_PROGRESS |
| `complete()` | status = IN_PROGRESS | status → COMPLETED |
| `cancel()` | status IN (SCHEDULED, IN_PROGRESS) | status → CANCELLED |

**Validaciones:**
- `endedAt >= startedAt`
- Detección de superposiciones con otros períodos activos del mismo empleado

---

### Aggregate Root — `ServiceZone` (CLS) ⚪ Pendiente de implementar

Zona operativa de la empresa para agrupar sucursales de clientes y asignar técnicos responsables.

> En el sistema legado: `customerLocationService` (CLS). Orbis lo llama `ServiceZone`.
>
> **Propiedad de la empresa, no del cliente.** CMS administra cuántas zonas existen y qué técnicos cubren cada una. Los `Site` de los clientes se asocian a una zona. Esto permite detectar atenciones "fuera de zona".

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| name | string | Nombre de la zona (ej: "Zona Norte", "Sierra Centro") |
| cities | ULID[] | IDs de ciudades que abarca (Location BC) |
| status | enum | `ACTIVE` / `INACTIVE` |
| deletedAt | datetime? | Soft delete |

> Los técnicos asignados a una zona se consultan via `Employee.serviceZoneId` — no hay lista en `ServiceZone`.

#### Comportamientos de ServiceZone

| Método | Guard | Efecto |
|---|---|---|
| `changeName(name)` | nombre único en el sistema | name actualizado |
| `addCity(cityId)` | ciudad no está en otra zona activa | cities[] actualizado |
| `removeCity(cityId)` | ciudad está en la lista | cities[] actualizado |
| `deactivate()` | status = ACTIVE | status → INACTIVE |
| `reactivate()` | status = INACTIVE | status → ACTIVE |
| `softDelete()` | sin empleados asignados y sin sites activos | deletedAt = now() |

#### Invariantes de ServiceZone

1. El nombre debe ser único en el sistema
2. Una ciudad no puede pertenecer a **dos zonas activas simultáneamente**
3. No se puede eliminar una zona que tenga empleados o sites activos asignados

#### Detección "Fuera de Zona"

La lógica vive en el Application Layer (o en una Query dedicada):

```
ticket.technician.serviceZoneId != ticket.terminal.site.serviceZoneId
   → atención "fuera de zona" = true
```

- Si el técnico no tiene zona (`serviceZoneId = null`) → se reporta como "sin zona asignada"
- Si el site no tiene zona (`serviceZoneId = null`) → se reporta como "site sin zona"
- El resultado es un atributo calculado del ticket visible en reportes y en el detalle de la atención

---

### Entidad — `EmploymentPeriod` ⚠️ Pendiente de implementar (baja prioridad)

Historial de períodos de empleo del empleado.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| employeeId | ULID | Referencia al Employee |
| startDate | date | Inicio del período |
| endDate | date? | Fin del período (`null` = activo) |

**Invariantes:**
- Los períodos deben estar ordenados cronológicamente y no pueden superponerse
- Solo el último período puede estar abierto (sin `endDate`)

---

## Queries esperadas

- Listar empleados con filtro por status, nombre
- Buscar empleado por id o número de identificación
- Listar empleados por zona de servicio (`serviceZoneId`)
- Listar períodos especiales por `employeeId` (con filtro por type y status)
- Ver disponibilidad de técnicos (empleados ACTIVE sin período especial activo)
- Listar zonas de servicio; ver empleados por zona

---

## Dependencias cross-BC

| Referencia | BC externo | Descripción |
|---|---|---|
| `Employee.userId?` | Identity | Usuario de acceso al sistema |
| `Employee.id` | Support | Técnicos asignados a tickets |
| `Employee.id` | Inventory | Creador de solicitudes de stock |
| `Employee.serviceZoneId` | Client.Site | Detectar atenciones fuera de zona (`Employee.serviceZoneId != Site.serviceZoneId`) |
