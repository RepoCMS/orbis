dd# BC Inventory — Especificación de Dominio

**Propósito:** Gestión del ciclo de vida completo de repuestos de cajeros: catálogo, ubicaciones, stock, movimientos, compras, reparaciones y solicitudes.

**Estado:** 🟡 Domain + Infrastructure completos. Application + Presentation pendientes. `Laboratory.php` stub pendiente de eliminar.

**Dependencias cross-BC:** Identity (usuarios de auditoría), Support (repuestos en tickets)

---

## Estrategia de Auditoría

Campos de auditoría directamente en las entidades del dominio (no hay tabla de audit log separada):

| Campo | Usado en |
|---|---|
| `createdBy: UserIdValueObject` | Toda entidad creada por usuario |
| `confirmedBy?: UserIdValueObject` | `Entry` |
| `cancelledBy?: UserIdValueObject` | `Entry`, `PurchaseOrder`, `Request` |
| `approvedBy?: UserIdValueObject` | `Request` |
| `rejectedBy?: UserIdValueObject` + `rejectionReason?` | `Request` |
| `startedBy?: UserIdValueObject` | `Repair` |
| `completedBy?: UserIdValueObject` | `Repair` |

> `AuthLog` permanece solo para Identity (eventos de seguridad). Los campos anteriores cubren la trazabilidad del MVP de Inventory.

---

## Entidades y Aggregates

### Aggregate Root — `Product` (Catálogo de Repuestos) ✅

Catálogo de tipos de repuestos. Cada `SerializedStock` y `NonserializedStock` referencia un `Product`.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| productCodeBySupplier | string | Código del proveedor (único, 1–50 chars) |
| name | string | Nombre del repuesto (1–150 chars) |
| description | string | Descripción técnica |
| deletedAt | datetime? | Soft delete |

> Si el producto no existe al recibir mercancía → se puede **crear desde el flujo de recepción** de la `Entry`.

#### Comportamientos del Product

| Método | Guard | Efecto |
|---|---|---|
| `Product::add(id, code, name, description)` | — | Crea nuevo producto |
| `update(name, description)` | — | Actualiza nombre y descripción |
| `softDelete()` | — | deletedAt = now() |

---

### Entidad — `Location` (Ubicaciones de Inventario) ✅

Lugar físico donde puede existir stock.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| name | string | Nombre de la ubicación (1–100 chars) |
| city | string | Ciudad donde está |
| address | string | Dirección física |
| type | `LocationType` | Tipo de ubicación |
| deletedAt | datetime? | Soft delete |

#### Enum `LocationType`

| Valor | Descripción |
|---|---|
| `WAREHOUSE` | Almacén central o sucursal |
| `LABORATORY` | Laboratorio de reparación |
| `BACKPACK` | Mochila de técnico (inventario portátil) |

> Los laboratorios **no son una entidad separada** — son `Location` con `type = LABORATORY`. El `Laboratory.php` stub en el código debe eliminarse porque es redundante.

> Las **mochilas** (`BACKPACK`) son el inventario portátil de cada técnico. El técnico lleva los repuestos en su mochila y los descuenta al usarlos en un ticket.

---

### Entidad — `SerializedStock` (Stock Serializado) ✅

Una unidad física única de un repuesto con número de serie.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| productId | ULID | Referencia a Product |
| serialNumber | string | Número de serie único en el sistema |
| locationId | ULID | Ubicación actual |
| status | `SerializedStockStatus` | Estado actual |
| deletedAt | datetime? | Soft delete |

#### Enum `SerializedStockStatus`

| Valor | Descripción |
|---|---|
| `AVAILABLE` | Disponible para usar o transferir |
| `RESERVED` | Bloqueada para un movimiento pendiente |
| `DAMAGED` | Dañada, pendiente de reparación o baja |
| `DECOMMISSIONED` | Dada de baja definitivamente |

#### Ciclo de vida del SerializedStock

```
REGISTER
   │
   ▼
AVAILABLE ──── reserve() ────► RESERVED ──── release() ────► AVAILABLE
   │                               │
   │                               │ completeTransfer(newLocationId)
   │                               ▼
   │                           AVAILABLE (nueva ubicación)
   │
   ▼ markDamaged()
DAMAGED ──── (Repair) ────► AVAILABLE (restaurado, posiblemente con nuevo serial)
   │
   ▼ decommission()
DECOMMISSIONED (final)
```

#### Comportamientos del SerializedStock

| Método | Guard | Efecto |
|---|---|---|
| `SerializedStock::register(id, productId, serialNumber, locationId)` | — | Crea con status AVAILABLE |
| `reserve()` | status = AVAILABLE | status → RESERVED |
| `release()` | status = RESERVED | status → AVAILABLE |
| `completeTransfer(newLocationId)` | status = RESERVED | locationId actualizado, status → AVAILABLE |
| `markDamaged()` | — | status → DAMAGED |
| `decommission()` | — | status → DECOMMISSIONED |

---

### Entidad — `NonserializedStock` (Stock No Serializado) ✅

Lote de repuesto fungible (sin serial) en una ubicación con un estado específico. Unicidad: `(productId, locationId, status)`.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| productId | ULID | Referencia a Product |
| locationId | ULID | Ubicación del lote |
| quantity | int | Cantidad actual |
| status | `NonserializedStockStatus` | Estado del lote |
| deletedAt | datetime? | Soft delete (auto al llegar a qty=0 en `reduce`) |

> Pueden coexistir múltiples registros del mismo producto en la misma ubicación con distintos estados: `AVAILABLE` + `RESERVED` + `DAMAGED`.

#### Comportamientos del NonserializedStock

| Método | Guard | Efecto |
|---|---|---|
| `NonserializedStock::initialize(id, productId, locationId, qty=0)` | — | Crea con status AVAILABLE |
| `addStock(amount)` | status = AVAILABLE | quantity += amount |
| `reduce(amount)` | amount ≤ quantity | quantity -= amount; si qty→0: softDelete auto |
| `reserve()` | status = AVAILABLE | status → RESERVED |
| `release()` | status = RESERVED | status → AVAILABLE |
| `markDamaged()` | — | status → DAMAGED |
| `decommission()` | — | status → DECOMMISSIONED |
| `restore()` | status = DAMAGED | status → AVAILABLE |

---

### Entidad — `StockMinimum` ⚪ Nuevo — pendiente de implementar

Control de stock mínimo por bodega. Genera alertas cuando el stock disponible cae por debajo del umbral.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| locationId | ULID | Referencia a Location (WAREHOUSE o BACKPACK) |
| productId | ULID | Referencia a Product |
| idealQuantity | int | Cantidad ideal a mantener |
| alertThreshold | int | Cantidad mínima antes de alertar |

> El sistema genera alertas (dashboard + notificación) cuando `stockActual ≤ alertThreshold`. El dashboard del coordinador muestra "cuánto stock queda en cada bodega".

---

### Entidad — `SerialHistory` ⚪ Nuevo — pendiente de implementar

Trazabilidad de cambios de número de serie de un repuesto serializado.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| serializedStockId | ULID | Referencia al SerializedStock actual |
| previousSerialNumber | string | Serial anterior |
| newSerialNumber | string | Serial nuevo |
| changedAt | datetime | Fecha del cambio |
| reason | enum | `REPAIR` / `TRANSFORMATION` |

> Cuando una parte serializada es reparada, se le asigna un **nuevo número de serie**. El sistema registra la cadena completa: `serial1 → serial2 → serial3 → ...`
>
> Consulta esperada: "la parte con código X, que inició con serial 1xcaA, ha cambiado de serial 5 veces y ha estado instalada en estos cajeros."

---

### Entidad — `Movement` (Movimiento de Stock) ✅

Registro del desplazamiento real de stock entre ubicaciones.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| productId | ULID | Referencia a Product |
| type | `MovementType` | `ENTRY` / `TRANSFER` / `EXIT` |
| sourceLocationId | ULID? | Origen (`null` para ENTRY) |
| destinationLocationId | ULID? | Destino (`null` para EXIT) |
| serializedStockId | ULID? | Si es stock serializado |
| quantity | int | Cantidad (siempre 1 para serializado) |
| status | `MovementStatus` | `PENDING` / `IN_TRANSIT` / `COMPLETED` / `CANCELLED` |
| notes | string? | Notas opcionales |
| createdAt | datetime | Fecha de creación |
| completedAt | datetime? | Fecha de completado |

---

### Entidad — `Request` (Solicitud de Movimiento) ✅ Domain implementado

Solicitud formal de un empleado para recibir repuestos desde una ubicación hacia su mochila u otra ubicación. Requiere aprobación.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| reference | string | Referencia auto-generada (ej: `SOL-2026-001`) |
| requestedBy | ULID | Quién solicitó (User en Identity BC) |
| productId | ULID | Repuesto solicitado |
| serializedStockId | ULID? | Si es unidad serializada específica |
| quantity | int | Cantidad (1 para serializado) |
| sourceLocationId | ULID | Origen (almacén/lab) |
| destinationLocationId | ULID | Destino (mochila u otra ubicación) |
| status | `RequestStatus` | Estado actual |
| notes | string? | Motivo o notas |
| approvedBy | ULID? | Quién aprobó |
| approvedAt | datetime? | Cuándo fue aprobada |
| rejectedBy | ULID? | Quién rechazó |
| rejectedAt | datetime? | Cuándo fue rechazada |
| rejectionReason | string? | Motivo del rechazo |
| fulfilledMovementId | ULID? | Movimiento generado al ejecutar |
| cancelledBy | ULID? | Quién canceló |
| createdAt | datetime | Fecha de creación |

#### Enum `RequestStatus`

| Valor | Descripción |
|---|---|
| `PENDING` | Enviada, esperando aprobación |
| `APPROVED` | Aprobada, pendiente de ejecutar |
| `AWAITING_TECHNICIAN_ACCEPTANCE` | **NUEVO** — Aprobada por bodeguero, pendiente de aceptación del técnico |
| `REJECTED` | Rechazada |
| `FULFILLED` | Movimiento ejecutado y completado |
| `CANCELLED` | Cancelada por el solicitante (solo desde PENDING) |

#### Ciclo de vida del Request (actualizado)

```
PENDING ──── approve(approvedBy) ─────────────────────────────► AWAITING_TECHNICIAN_ACCEPTANCE
   │               [PENDING → APPROVED → AWAITING en una sola acción]         │
   │                                                            accept()       │ rejectByTechnician()
   │                                                               │           │
   │                                                               ↓           ↓
   │                                                           FULFILLED     REJECTED (definitivo)
   │
   ├── reject(rejectedBy, reason) ────► REJECTED
   │
   └── cancel(cancelledBy) ────► CANCELLED
```

> **AWAITING_TECHNICIAN_ACCEPTANCE:** El bodeguero aprueba la solicitud (un solo paso PENDING → AWAITING) → el técnico recibe notificación → el técnico **acepta** (la parte ingresa a su mochila y la solicitud queda FULFILLED) o **rechaza definitivamente** (debe crear nueva solicitud).
> El estado `APPROVED` existe en el enum como estado intermedio interno pero no es visible en la UI de forma independiente.

**Casos de emergencia:** El sistema permite registrar repuestos usados en un ticket sin una solicitud formal previa (`Request`). Esto no afecta la integridad del ticket ni del inventario — el técnico indica la parte usada directamente y el Application Service ajusta el stock de su mochila.

#### Comportamientos del Request

| Método | Guard | Efecto |
|---|---|---|
| `Request::submit(...)` | — | Crea con status PENDING |
| `approve(approvedBy)` | status = PENDING | status → APPROVED |
| `notifyTechnicianAcceptance()` | status = APPROVED | status → AWAITING_TECHNICIAN_ACCEPTANCE |
| `acceptByTechnician()` | status = AWAITING | status → FULFILLED, Movement creado |
| `rejectByTechnician(rejectedBy, reason)` | status = AWAITING | status → REJECTED definitivo; técnico debe crear nueva solicitud si lo requiere |
| `reject(rejectedBy, reason)` | status = PENDING | status → REJECTED |
| `cancel(cancelledBy)` | status = PENDING | status → CANCELLED |

---

### Entidad — `PurchaseOrder` (Orden de Compra) ✅

Orden de compra de repuestos a un proveedor externo. Al recibir la mercancía se crea un `Entry`.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| reference | string | Número/referencia de la orden (único) |
| status | `PurchaseOrderStatus` | `OPEN` / `PARTIAL` / `COMPLETED` / `CANCELLED` |
| notes | string? | Notas opcionales |
| createdBy | ULID | Quién creó (User en Identity BC) |
| createdAt | datetime | Fecha de creación |

---

### Entidad — `PurchaseOrderItem` ✅

Línea de una `PurchaseOrder`.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| purchaseOrderId | ULID | Referencia a PurchaseOrder |
| productId | ULID | Producto comprado |
| quantityOrdered | int | Cantidad pedida |
| quantityReceived | int | Cantidad ya recibida (se actualiza al confirmar Entry) |

---

### Entidad — `Entry` (Recepción/Entrada de Inventario) ✅

Recepción física de mercancía. Al confirmarse, genera `SerializedStock` o incrementa `NonserializedStock`.

> Un **Entry (importe)** puede satisfacer múltiples `PurchaseOrder` — el bodeguero registra el lote de mercancía llegada y la distribuye entre las órdenes correspondientes.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| reference | string | Número de recepción (único) |
| status | `EntryStatus` | `DRAFT` / `CONFIRMED` / `CANCELLED` |
| notes | string? | Notas |
| createdBy | ULID | Quién creó |
| confirmedBy | ULID? | Quién confirmó |
| cancelledBy | ULID? | Quién canceló |
| createdAt | datetime | Fecha de creación |
| confirmedAt | datetime? | Cuándo fue confirmada |

---

### Entidad — `EntryItem` ✅

Línea de una `Entry`.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| entryId | ULID | Referencia a Entry |
| productId | ULID | Producto recibido |
| purchaseOrderItemId | ULID? | Línea de PO que satisface (opcional) |
| serialNumber | string? | Serial de la unidad (si aplica) |
| quantity | int | Cantidad (siempre 1 si tiene serial) |
| destinationLocationId | ULID | Dónde se almacenará |

---

### Entidad — `Repair` (Reparación de Repuesto) ✅

Proceso de reparación de un repuesto serializado dañado en un laboratorio.

> El **laboratorista** decide si el repuesto dañado va a reparación o directamente a baja — se registra quién decide.
>
> **Restricción operativa:** los repuestos dañados **no van directo del técnico al laboratorio** — primero van a la bodega principal (UIO o GYE), y desde allí el laboratorista los recibe y decide.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| serializedStockId | ULID | Repuesto siendo reparado |
| locationId | ULID | Laboratorio donde se repara (`Location` con `type=LABORATORY`) |
| technicianId | ULID? | Técnico/laboratorista asignado (User en Identity BC) |
| status | `RepairStatus` | `PENDING` / `IN_PROGRESS` / `COMPLETED` / `UNREPAIRABLE` |
| notes | string? | Observaciones iniciales |
| startedAt | datetime? | Inicio de la reparación |
| completedAt | datetime? | Fin de la reparación |
| completionNotes | string? | Resultado/notas de cierre |

> Si reparado → `SerializedStock` vuelve a `AVAILABLE`, posiblemente con **nuevo serial** (registrar en `SerialHistory`).
> Si irreparable → `SerializedStock.decommission()`.

---

### Entidad — `Transformation` (Canje de Repuesto) ✅

Canje de un repuesto serializado dañado por uno nuevo.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| outgoingStockId | ULID | Repuesto que sale (dañado/canjeado) |
| incomingStockId | ULID? | Repuesto que entra (nuevo) — puede ser null si aún no llegó |
| locationId | ULID | Ubicación donde ocurre la transformación |
| notes | string? | Motivo u observaciones |
| transformedAt | datetime | Fecha de la transformación |
| createdAt | datetime | Fecha de registro |

---

## Flujos de Negocio

### Flujo A — Ingreso de Stock (PurchaseOrder → Entry)

```
1. Bodeguero crea PurchaseOrder con PurchaseOrderItems
2. Al llegar la mercancía, crea Entry (DRAFT) con EntryItems
   → Un Entry puede satisfacer productos de múltiples POs
   → Si el producto no existe → se puede crear desde este flujo
3. Al confirmar Entry:
   - Si producto con serial → se crea SerializedStock (AVAILABLE)
   - Si producto sin serial → se incrementa NonserializedStock (AVAILABLE)
   - PurchaseOrderItem.quantityReceived se actualiza
   - Si todos los POItems completos → PO pasa a COMPLETED
4. Se crea Movement(ENTRY) como registro del ingreso
```

### Flujo B — Solicitud y Transferencia de Stock

```
1. Técnico crea Request (PENDING) solicitando repuesto de almacén a su mochila
2. Bodeguero recibe notificación (sistema + email/WhatsApp)
3. Bodeguero revisa stock → aprueba o rechaza
4. Si aprueba → Request pasa a AWAITING_TECHNICIAN_ACCEPTANCE
5. Técnico recibe notificación → acepta o rechaza
6. Si acepta:
   - Si serializado: SerializedStock.reserve() → completeTransfer(mochila)
   - Si no serializado: reduce lote AVAILABLE origen, crea/incrementa AVAILABLE en mochila
   - Se crea Movement(TRANSFER) → Request pasa a FULFILLED
```

### Flujo C — Reparación de Repuesto Dañado

```
1. SerializedStock.markDamaged() (al registrar salida del cajero en ticket)
2. Técnico transfiere parte dañada a bodega principal (NO directo al lab)
3. Bodeguero recibe → laboratorista decide: reparar o dar de baja
4. Si reparación → Repair creado en laboratorio
5. Laboratorista inicia → Repair.start() → IN_PROGRESS
6. Si repara exitosamente → Repair.complete(notes) → SerializedStock a AVAILABLE
   → Si se asigna nuevo serial → registrar en SerialHistory
7. Si irreparable → Repair.markUnrepairable() → SerializedStock.decommission()
```

### Flujo D — Transformación/Canje

```
1. Repuesto dañado se registra en Transformation como outgoing
2. Repuesto de reemplazo llega vía Entry → se registra como incoming
3. SerializedStock outgoing → DECOMMISSIONED
4. SerializedStock incoming → AVAILABLE en ubicación correspondiente
```

---

## Queries esperadas

- Listar productos con filtro por nombre/código
- Ver stock de un producto (serializado y no serializado) por ubicación
- Listar movimientos por ubicación, producto, rango de fechas
- Listar órdenes de compra por estado, rango de fechas
- Ver estado de una reparación
- Listar solicitudes pendientes de aprobación (por bodeguero)
- Listar solicitudes AWAITING_TECHNICIAN_ACCEPTANCE (por técnico)
- Dashboard: stock bajo mínimo por bodega
- Trazabilidad completa de un serial: cadena de serials + cajeros donde estuvo instalado

---

## Dependencias cross-BC

| Referencia | BC externo | Descripción |
|---|---|---|
| `Request.requestedBy` | Identity | Técnico que solicita |
| `Entry.createdBy` / `confirmedBy` | Identity | Auditoría de recepciones |
| `Repair.technicianId` | Identity | Laboratorista asignado |
| `TicketSparePart.productCode` | Support | Repuestos usados en tickets |
| `Location.city` | Location (implícito) | Ciudad del almacén |
