# BC Client — Especificación de Dominio

**Propósito:** Gestión de clientes (bancos/cooperativas), sus contactos, parque de cajeros, zonas de servicio, contratos, SLAs, garantías y órdenes de venta.

**Estado:** ✅ Client / Contact / Catalogue / Terminal / Site — Completos. `ClientServiceZone`, `Contract`, `SlaPolicy`, `TerminalWarranty`, `ClientSaleOrder` → ⚪ Pendientes.

**Dependencias cross-BC:** Location (ciudades), Identity (usuarios de contactos), Support (tickets por terminal)

---

## Entidades y Aggregates

### Aggregate Root — `Client`

`Client` representa un banco o institución financiera que contrata los servicios de la empresa.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| name | string | Nombre del banco (único en el sistema) |
| ruc | string | RUC ecuatoriano (único en el sistema) |
| address | string? | Dirección de la sede principal (opcional) |
| status | enum | `ACTIVE` / `INACTIVE` |
| deletedAt | datetime? | Soft delete |

#### Value Objects de Client

| Value Object | Reglas |
|---|---|
| `ClientNameValueObject` | Normalizado (trim + colapso de espacios), 5–100 caracteres, único |
| `RucValueObject` | Exactamente 13 dígitos. Dígitos 1-2: código de provincia (01–24). Dígito 3: tipo (0–5 natural, 6 pública, 9 sociedad). Dígito 13: verificador (módulo 10 para naturales, módulo 11 para sociedades/públicas). Único en el sistema. |

#### Estados del Client

```
ACTIVE ──── deactivate() ────► INACTIVE
  ▲                                │
  └──────── reactivate() ──────────┘
```

> Al desactivar un cliente: queda congelado — no se pueden crear nuevos tickets, órdenes de compra ni contratos. Los registros existentes permanecen visibles.

#### Comportamientos del Client

| Método | Guard | Efecto |
|---|---|---|
| `deactivate()` | status = ACTIVE | status → INACTIVE |
| `reactivate()` | status = INACTIVE | status → ACTIVE |
| `changeName(name)` | nombre único | name actualizado |
| `changeAddress(address)` | — | address actualizado |
| `softDelete()` | — | deletedAt = now() |

> **Efecto cascada del `softDelete()`** — coordinado por el Application Service:
> 1. Todos los tickets activos del cliente se cancelan (cross-BC → Support)
> 2. Todos los contactos activos se desactivan. Si tienen `userId` → su `User` se desactiva (cross-BC → Identity)
> 3. Se ejecuta `Client.softDelete()`

#### Invariantes del Client

1. `name` y `ruc` deben ser únicos en todo el sistema
2. Un `Client` `INACTIVE` no puede ser origen de nuevos tickets, órdenes de compra ni contratos
3. Los contactos son **opcionales** — un cliente puede existir sin contactos registrados
4. `RegisterClientCommand` solo registra el cliente; los contactos se agregan después vía `AddContactCommand`

---

### Entidad — `Contact`

Persona física que es punto de contacto de un `Client`. Puede tener acceso opcional al portal web.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| clientId | ULID | Referencia al Client dueño |
| userId | ULID? | Referencia opcional a User (Identity BC) — acceso al portal |
| firstName | string | Nombre, normalizado, 2–100 caracteres |
| lastName | string | Apellido, normalizado, 2–100 caracteres |
| email | string | Email normalizado a minúsculas |
| phoneNumber | string | Teléfono ecuatoriano (10 dígitos, inicia con `09`) |
| role | string | Cargo en el banco (ej: "Supervisor de ATMs") |
| status | enum | `ACTIVE` / `INACTIVE` |
| deletedAt | datetime? | Soft delete |

#### Value Objects de Contact

| Value Object | Reglas |
|---|---|
| `ContactEmailValueObject` | Normalizado a minúsculas, formato email válido |
| `ContactPhoneNumberValueObject` | 10 dígitos numéricos, inicia con `09` |
| `ContactRoleValueObject` | Texto libre, 2–100 caracteres, normalizado |

#### Invariantes del Contact

1. El `email` debe ser único entre los contactos del mismo `Client`
2. Un `Contact` con `userId` no puede reasignarse a otro usuario sin desvincular primero
3. Al **desactivar** un `Contact` con `userId` → `User` se desactiva (cross-BC)
4. Al **eliminar lógicamente** un `Contact` con `userId` → `User` se desactiva (cross-BC)
5. Al **desvincular** (`unlinkUser()`) → el `User` permanece `ACTIVE`; el contacto puede seguir recibiendo notificaciones sin login
6. El último `Contact` activo de un `Client` **no puede** ser desactivado ni eliminado

#### Comportamientos del Contact

| Método | Guard | Efecto |
|---|---|---|
| `createContact()` | - | contacto de cliente creado |
| `changeName(first, last)` | — | nombres actualizados |
| `changeEmail(email)` | email único en el cliente | email actualizado |
| `changePhoneNumber(phone)` | — | phoneNumber actualizado |
| `changeRole(role)` | — | role actualizado |
| `linkUser(userId)` | userId es null actualmente | userId = userId |
| `unlinkUser()` | userId != null | userId = null |
| `deactivate()` | status = ACTIVE, no es el último activo | status → INACTIVE; si tiene userId → User.deactivate() cross-BC |
| `reactivate()` | status = INACTIVE | status → ACTIVE |
| `softDelete()` | no es el último activo | deletedAt = now(); si tiene userId → User.deactivate() cross-BC |

---

> **⚠️ Decisión arquitectónica — CLS pertenece a Organization BC, no a Client BC.**
> La zona de servicio (CLS) es una construcción operativa de **la empresa**, no del cliente.
> CMS define las zonas y asigna técnicos a ellas. Los `Site` del cliente se asocian a una zona para
> determinar si una atención fue "dentro" o "fuera de zona".
> Ver especificación completa: `docs/requirements/03-ORGANIZATION.md` — entidad `ServiceZone`.

---

### Entidad — `Terminal` (Catálogo de Modelos ATM) ✅ Implementado

> **Nota:** `Terminal` aquí representa el modelo/ficha técnica, no la unidad física instalada.

Modelo de cajero automático en el parque de un cliente.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| clientId | ULID | Cliente dueño |
| serialNumber | string | Serial del modelo (único por cliente) |
| vendor | enum | `NCR`, `DIEBOLD`, `WINCOR`, `NAUTILUS_HYOSUNG`, `GRG`, `HITACHI`, `GLORY`, `OTHER` |
| type | enum | `THROUGH_THE_WALL`, `LOBBY`, `DRIVE_THRU`, `OTHER` |
| functionality | enum | `DISPENSER`, `RECYCLER`, `OTHER` |
| status | enum | `ACTIVE` / `INACTIVE` |
| deletedAt | datetime? | Soft delete |

---

### Entidad — `TerminalAssignment` (Unidad ATM Desplegada) ✅ Implementado

Unidad física de cajero instalada en una sucursal. Referenciada por Support BC para crear tickets.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| terminalId | ULID | ATM al que pertenece (`Terminal`) |
| cityId | ULID | Donde está instalado (`City`) |
| name | string | Nombre descriptivo de la terminal |
| idAsignedByClient | string | Código del cliente para este cajero |
| status | enum | `OPERATIVE`, `INOPERATIVE` |
| deletedAt | datetime? | Soft delete |

---

### Entidad — `Contract` ⚪ Pendiente de implementar

Contrato de servicios entre la empresa y el cliente. Un contrato puede cubrir todos los cajeros del cliente o un grupo/proyecto específico.

> Los contratos nacen generalmente de la **compra de cajeros** (ClientSaleOrder). Tienen fecha de inicio/vencimiento y se renuevan.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| clientId | ULID | Referencia al Client |
| title | string | Título descriptivo del contrato |
| startDate | date | Fecha de inicio |
| endDate | date | Fecha de vencimiento |
| status | enum | `ACTIVE` / `EXPIRED` / `CANCELLED` |
| notes | string? | Observaciones adicionales |
| deletedAt | datetime? | Soft delete |

> ⚠️ Pendiente validar campos adicionales con contrato real del cliente.

---

### Entidad — `ContractCoverage` ⚪ Pendiente

Define qué terminales cubre un `Contract`. Puede ser por `terminalId` individual o por `groupTag` (ej: proyecto).

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| contractId | ULID | Referencia al Contract |
| terminalId | ULID? | Terminal específica cubierta |
| groupTag | string? | Etiqueta de grupo (ej: "Proyecto Entintado 2025") |

---

### Entidad — `SlaPolicy` ⚪ Pendiente

Define los parámetros de nivel de servicio (SLA) para un contrato. El reloj SLA empieza cuando se crea el ticket y termina cuando el coordinador aprueba la atención.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| contractId | ULID | Referencia al Contract |
| metricType | enum | `RESPONSE_TIME` / `UPTIME` / `DOWNTIME` / `MAINTENANCE_COUNT` |
| targetValue | decimal | Valor objetivo (ej: 4.0 horas, 99.5%) |
| unit | string | Unidad de medida (ej: `hours`, `percent`, `count`) |
| penaltyAmount | decimal? | Monto de penalización por incumplimiento |

---

### Entidad — `TerminalWarranty` ⚪ Pendiente

Garantía de **contrato** (no de fábrica) asociada a una terminal física. Cubre el equipo completo.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| terminalId | ULID | Terminal física (`TerminalAssignment`) |
| contractId | ULID | Contrato de origen |
| startsAt | date | Inicio de garantía |
| expiresAt | date | Vencimiento de garantía |
| status | enum | `ACTIVE` / `EXPIRED` |
| alertThresholdDays | int | Días de anticipación para alertar (ej: 30) |

> El sistema genera alertas cuando la garantía está próxima a vencer. Visible tanto para CMS (internamente) como para el cliente en su portal.

---

### Entidad — `ClientSaleOrder` ⚪ Pendiente

Orden de venta de cajeros al cliente. Permite registrar qué cajeros compró el cliente y cargarlos masivamente al catálogo.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| clientId | ULID | Referencia al Client |
| reference | string | Número/referencia de la orden |
| orderDate | date | Fecha de la orden |
| status | enum | `PENDING` / `COMPLETED` / `CANCELLED` |
| deletedAt | datetime? | Soft delete |

**Comportamiento clave:** Al marcar una orden como completada, los ítems se cargan masivamente al catálogo de terminales del cliente (`Terminal`) con un click.

---

### Entidad — `ClientSaleOrderItem` ⚪ Pendiente

Línea de una `ClientSaleOrder` para un modelo específico.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| orderId | ULID | Referencia a ClientSaleOrder |
| terminalModelId | ULID | Modelo ATM (`Terminal` catálogo) |
| quantity | int | Cantidad de cajeros comprados |
| installedAt | datetime? | Fecha de instalación efectiva (opcional) |

---

## Portal del Cliente

El cliente (rol `CLIENT_PORTAL`) accede con credenciales creadas por CMS:

- Solo puede ver tickets/atenciones **relacionados a sus terminales o proyectos**
- Solo puede descargar reportes de tickets **cerrados y aprobados**
- Puede ver el **estado en tiempo real** de un ticket activo
- **No puede crear tickets** desde el portal — solo CMS crea tickets
- **Visibilidad por campo**: el coordinador define qué información es visible al cliente y cuál no (ej: repuestos "internos" no aparecen en el documento del cliente)
- Puede estar configurado para recibir alertas de estado del ticket por correo
- Puede ver las **garantías de sus terminales** (vencimientos próximos)

---

## Invariantes de negocio

1. Un `Client` `INACTIVE` o `deletedAt != null` bloquea la creación de: `Contact`, `Terminal`, `TerminalAssignment`, tickets (Support BC), `Contract`
2. `Contact` `INACTIVE` o `deletedAt != null` bloquea la vinculación de `User`
3. Al reactivar un `Client`, sus `Contact`s **no se reactivan automáticamente** — el operador los reactiva individualmente
4. Un `TerminalAssignment` referencia a un `Site` del mismo `Client` — nunca de otro cliente
5. `Contact` `INACTIVE` o `deletedAt != null` no permite  `User` logearse
2. `Contact` `INACTIVE` + `deletedAt != null` deleted no puede recibir correos de notificacion, `User` es `INACTIVE`

---

## Queries esperadas

- Buscar cliente por id o RUC; listar clientes con filtro por status
- Buscar contacto por id; listar contactos por `clientId` con filtro por status
- Obtener todos los cajeros de un cliente agrupados por site/zona
- Listar contratos por `clientId`; ver SLAs de un contrato
- Ver garantías próximas a vencer (para dashboard de coordinador)
- Listar órdenes de venta por `clientId`
- Listar tickets por cliente (cross-BC → Support)
- Detectar tickets atendidos fuera de zona: `ticket.technician.serviceZoneId != ticket.terminal.site.serviceZoneId` (cross-BC → Support + Organization)

---

## Dependencias cross-BC

| Referencia | BC externo | Descripción |
|---|---|---|
| `Contact.userId?` | Identity | Usuario de portal del contacto |
| `Site.cityId` | Location | Ciudad donde está la sucursal |
| `Site.serviceZoneId?` | Organization | Zona de servicio a la que pertenece la sucursal |
| `TerminalAssignment.id` | Support | Support crea tickets referenciando esta entidad |
