# Feature Specification: Solicitudes multi-item (líneas múltiples)

**Feature Branch**: `012-multi-item-request`

**Created**: 2026-06-10

**Updated**: 2026-07-30

**Status**: Implemented (100%)

**Input**: Un técnico de campo necesita solicitar múltiples repuestos (con y sin serie) en una sola solicitud, en lugar de crear una solicitud por cada item. Cada item debe poder ser despachado independientemente (fulfill parcial).

---

## Business Rules

| Regla | Descripción |
|-------|-------------|
| **MR1** | Una solicitud puede contener 1 o más items (líneas), cada una con producto, cantidad y opcionalmente serial |
| **MR2** | Los items pueden ser serializados (con `serializedStockId`) o no serializados (solo cantidad) — mezclados en la misma solicitud |
| **MR3** | Cada item tiene su propio estado: PENDING, FULFILLED, CANCELLED |
| **MR4** | El estado global del Request se deriva de los items: PENDING si todos pending, APPROVED si al menos uno aprobado, PARTIALLY_FULFILLED si algunos fulfilled, FULFILLED si todos fulfilled, CANCELLED si todos cancelled |
| **MR5** | El fulfillment (despacho) es por item individual — cada item fulfilled genera su propio Movement |
| **MR6** | Todos los items comparten el mismo `sourceLocationId` y `destinationLocationId` de la solicitud |
| **MR7** | Solo se puede cancelar un item si la solicitud está APPROVED o PARTIALLY_FULFILLED y el item está PENDING |
| **MR8** | Cancelar la solicitud completa cancela todos los items pendientes |
| **MR9** | La aprobación/rechazo es a nivel de solicitud (no por item) — al aprobar, todos los items pasan a APPROVED |

---

## Tabla comparativa: Antes vs Después

| Aspecto | Antes (single-item) | Después (multi-item) |
|---------|---------------------|----------------------|
| Request.productId | `private ProductIdValueObject` | Eliminado — se mueve a items |
| Request.serializedStockId | `private ?SerializedStockIdValueObject` | Eliminado — se mueve a items |
| Request.quantity | `private int` | Eliminado — se mueve a items |
| Request.fulfilledMovementId | `private ?MovementIdValueObject` | Eliminado — cada item tiene su propio Movement |
| Request.fulfill() | Toma un solo MovementId | Eliminado — se usa fulfillItem() |
| RequestItem (nueva entidad) | — | `productId`, `serializedStockId`, `quantity`, `status`, `fulfilledMovementId` |
| RequestStatus | `FULFILLED` | Se agrega `PARTIALLY_FULFILLED` |
| RequestItemStatus (nuevo enum) | — | PENDING, FULFILLED, CANCELLED |

---

## User Stories

### User Story 1 — Técnico crea solicitud con 2 items (happy path)

**Acceptance Scenarios**:

1. **Given** un técnico autenticado con permiso `spare_part.request`, **When** accede a `/inventory/requests/new`, **Then**:
   - El formulario muestra una tabla de items con 1 fila inicial
   - Cada fila tiene: producto (select), cantidad (number), serial opcional (text)
   - Hay un botón "Agregar item" que añade una fila
   - Todos los items comparten el origen y destino igual que hoy

2. **Given** el técnico agrega 2 items (producto A cantidad 1, producto B cantidad 3 serial X), **When** envía, **Then**:
   - Se crea 1 Request con 2 RequestItem
   - Ambos items inician en estado PENDING
   - El status del Request es PENDING

3. **Given** el técnico carga la página de detalle de la solicitud, **Then**:
   - Ve una tabla con todos los items: producto, cantidad, serial, estado
   - El status global se muestra como "Pendiente"
   - Los botones de acción dependen del status global

### User Story 2 — Aprobación y fulfill parcial

**Acceptance Scenarios**:

1. **Given** un Request con 2 items (ambos PENDING), **When** un admin lo aprueba, **Then**:
   - Request.status = APPROVED
   - Todos los items quedan en PENDING (el approve es a nivel Request, no cambia items)
   - Ahora aparecen botones "Despachar" por cada item

2. **Given** un Request APPROVED con 2 items, **When** el admin despacha el item 1, **Then**:
   - Se crea un Movement para ese item (mismo origen/destino del Request)
   - RequestItem.status = FULFILLED
   - RequestItem.fulfilledMovementId = ID del Movement creado
   - Si el item es no-serializado: crea Movement con el producto y cantidad del item
   - Si el item es serializado: crea Movement con el stock serializado específico
   - Request.status = PARTIALLY_FULFILLED

3. **Given** un Request con 1 item fulfilled y 1 pending, **When** el admin despacha el item 2, **Then**:
   - RequestItem.status = FULFILLED (segundo item)
   - Request.status = FULFILLED
   - Ambos items tienen su propio MovementId

### User Story 3 — Cancelación de item individual

**Acceptance Scenarios**:

1. **Given** un Request APPROVED con 2 items, **When** el admin cancela el item 1 (pending), **Then**:
   - RequestItem.status = CANCELLED (item 1)
   - Request.status = PARTIALLY_FULFILLED (si hay otros items pending/fulfilled)
   - O Request.status = CANCELLED (si todos los items están cancelled)

2. **Given** un Request PENDING, **When** el técnico cancela la solicitud completa, **Then**:
   - Request.status = CANCELLED
   - Todos los RequestItem.status = CANCELLED

### User Story 4 — Mixed items (serializado + no serializado)

**Acceptance Scenarios**:

1. **Given** un técnico en el formulario, **When** selecciona un producto serializado y marca "Stock serializado", **Then**:
   - Aparece campo para ingresar ULID del serial
   - Cantidad se fija en 1 (implícito para serializados)

2. **Given** un técnico crea una solicitud con item 1 (producto serializado A) e item 2 (producto no serializado B, cantidad 5), **Then**:
   - Ambos items se guardan correctamente
   - Al despachar item 1: se usa `SerializedStock::completeTransfer()`
   - Al despachar item 2: se usa `NonserializedStock::reserve()` + split pattern

---

## Diseño

### Domain

#### Nuevo Enum: `RequestItemStatus`
```php
enum RequestItemStatus: string
{
    case PENDING   = 'pending';
    case FULFILLED = 'fulfilled';
    case CANCELLED = 'cancelled';
}
```

#### Modificado: `RequestStatus`
Agregar `PARTIALLY_FULFILLED`:
```php
case PARTIALLY_FULFILLED = 'partially_fulfilled';
```

#### Nueva Entidad: `RequestItem`
```php
final class RequestItem
{
    // constructor private + factory method
    public function __construct(
        private RequestItemIdValueObject $id,
        private ProductIdValueObject $productId,
        private ?SerializedStockIdValueObject $serializedStockId,
        private int $quantity,
        private RequestItemStatus $status,
        private ?MovementIdValueObject $fulfilledMovementId,
    ) {}

    public static function create(
        RequestItemIdValueObject $id,
        ProductIdValueObject $productId,
        ?SerializedStockIdValueObject $serializedStockId,
        int $quantity,
    ): self;

    public function fulfill(MovementIdValueObject $movementId): void;
    public function cancel(): void;
    
    // accessors sin get prefix
}
```

#### Modificado: `Request`
- Eliminar: `productId`, `serializedStockId`, `quantity`, `fulfilledMovementId`
- Agregar: `Collection<RequestItem> $items`
- Modificar `Request::submit()` — recibe array de items en lugar de producto único
- Agregar métodos delegados:
  - `items(): array` — devuelve $items->toArray()
  - `pendingItems(): array`
  - `fulfilledItems(): array`
  - `fulfillItem(RequestItemIdValueObject $itemId, MovementIdValueObject $movementId): void`
  - `cancelItem(RequestItemIdValueObject $itemId): void`
  - `allItemsCancelled(): bool`
  - `allItemsFulfilled(): bool`
- `derivedStatus(): RequestStatus` — calcula el status global desde los items
  - Si todos los items CANCELLED → CANCELLED
  - Si todos los items FULFILLED → FULFILLED
  - Si hay items fulfilled y otros no → PARTIALLY_FULFILLED
  - En cualquier otro caso → mantener el status actual del Request (PENDING, APPROVED, AWAITING_TECHNICIAN_ACCEPTANCE, REJECTED)
- `cancel()` — también cancela todos los items pendientes
- `fulfill()` se elimina, reemplazado por `fulfillItem()`

### Application

#### Nuevo ValueObject: `RequestItemIdValueObject`
```php
final readonly class RequestItemIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new RequestItemIdException('Formato de ID de item de solicitud inválido.');
    }
}
```

**Exception asociada:**
```php
final class RequestItemIdException extends DomainException {}
```

#### Nuevo ValueObject: `RequestItemCommandInput`
```php
final readonly class RequestItemCommandInput
{
    public function __construct(
        public string $productId,
        public ?string $serializedStockId,
        public int $quantity,
    ) {}
}
```

#### Modificado: `SubmitRequestCommand`
```php
final readonly class SubmitRequestCommand
{
    public function __construct(
        /** @var RequestItemCommandInput[] */
        public array $items,       // antes: productId, serializedStockId, quantity
        public string $sourceLocationId,
        public string $destinationLocationId,
        public string $requestedBy,
        public ?string $notes,
    ) {}
}
```

#### Modificado: `SubmitRequestHandler`
- Iterar items, crear `RequestItem` entities
- Llamar `Request::submit()` con array de items
- Generar `RequestItemIdValueObject` para cada item

#### Nuevo Command: `FulfillRequestItemCommand` + `FulfillRequestItemHandler`
```php
final readonly class FulfillRequestItemCommand
{
    public function __construct(
        public string $requestId,
        public string $itemId,     // RequestItemId
    ) {}
}
```
- Busca Request por ID
- Busca RequestItem por ID dentro del Request
- Crea Movement según tipo, usando el patrón del `FulfillRequestHandler` existente:
  - **Serializado**: `completeTransfer(destinationLocationId)` en el `SerializedStock` — mueve el stock a la ubicación destino, el Movement se crea con quantity=1
  - **No serializado**: `reserve()` en el `NonserializedStock` de origen → split pattern (reduce origen, crea batch RESERVED). Movement se crea con quantity y productId del item
- El Movement se crea via `MovementService` o directamente (como en `FulfillRequestHandler`)
- Movements creados se completan automáticamente (`$movement->complete()`)
- Llama `$request->fulfillItem($itemId, $movementId)`
- Persiste
- Usa transacción manual (como `FulfillRequestHandler`)
- Publica `RequestItemFulfilled` domain event

#### Modificado: `ApproveRequestHandler`
- Ya no cambia status de items (solo setea Request.status = APPROVED)

#### Modificado: `FulfillRequestHandler`
- **Se elimina.** El fulfill ahora es por item vía `FulfillRequestItemHandler`.
- No existe botón "Despachar todo" — cada item se despacha individualmente.

#### Aclaración: flujo `AcceptByTechnicianHandler` (técnico)
- `AcceptByTechnicianHandler` **se mantiene sin cambios** a nivel de Request.
- Cuando el técnico acepta, **todos los items pendientes se fulfillan** en bulk.
  - Internamente itera los items PENDING, ejecuta la misma lógica que `FulfillRequestItemHandler` para cada uno (crea Movement, llama `fulfillItem()`).
  - Publica `RequestItemFulfilled` por cada item.
  - Si algún item falla, el técnico puede reintentar (no hay transacción cross-item).
- `RejectByTechnicianHandler` se mantiene igual (rechaza todo el Request).

#### Modificado: `CancelRequestHandler`
- Llama `$request->cancel()` que internamente cancela todos los items pendientes

#### Modificado: `RequestResponse`
- Reemplazar `productId`, `productName`, `serializedStockId`, `quantity` por `items: RequestItemResponse[]`
- Agregar `fulfilledItemsCount`, `pendingItemsCount`
- Corregir `statusLabel` existente: agregar `AWAITING_TECHNICIAN_ACCEPTANCE => 'Pendiente de aceptación del técnico'` y `PARTIALLY_FULFILLED => 'Parcialmente ejecutada'`

#### Nuevo: `RequestItemResponse`
```php
final readonly class RequestItemResponse
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $productName,
        public ?string $serializedStockId,
        public int $quantity,
        public string $status,
        public string $statusLabel,
        public ?string $fulfilledMovementId,
    ) {}
}
```

### N+1 prevention

`ListRequestsHandler` devuelve `RequestResponse[]` que ahora incluye `items[]`. Sin eager-loading, cada request dispara un query extra.

Solución en `DoctrineRequestRepository`:
- Agregar método `findAllWithItems(): array` que hace JOIN FETCH en `items`
- Modificar `findAll()` existente para usar eager-loading, O crear nuevo método

### Infrastructure

#### ORM: `RequestItem.orm.xml`
```xml
<entity name="App\Inventory\Domain\Entities\RequestItem" table="stock_request_items">
    <id name="id" type="request_item_id" column="id" length="26" />
    <field name="productId" type="product_id" column="product_id" length="26" />
    <field name="serializedStockId" type="serialized_stock_id" column="serialized_stock_id" length="26" nullable="true" />
    <field name="quantity" type="integer" column="quantity" />
    <field name="status" type="request_item_status" column="status" length="20" />
    <field name="fulfilledMovementId" type="movement_id" column="fulfilled_movement_id" length="26" nullable="true" />
    <many-to-one field="request" target-entity="App\Inventory\Domain\Entities\Request" inversed-by="items">
        <join-column name="request_id" referenced-column-name="id" />
    </many-to-one>
</entity>
```

#### ORM: `Request.orm.xml`
- Eliminar campos: `productId`, `serializedStockId`, `quantity`, `fulfilledMovementId`
- Agregar:
```xml
<one-to-many field="items" target-entity="App\Inventory\Domain\Entities\RequestItem" mapped-by="request" cascade="persist,remove" orphan-removal="true">
    <order-by>
        <order-by-field name="id" direction="ASC" />
    </order-by>
</one-to-many>
```

#### Nuevo DBAL Type: `RequestItemStatusEnumType`
Igual patrón que `RequestStatusEnumType`.

#### Nuevo DBAL Type: `RequestItemIdType`
Igual patrón que otros ULID types.

### Presentation

#### Formulario (`form.html.twig`)
- Reemplazar inputs simples de producto/cantidad/serial por una tabla dinámica
- Cada fila: select de producto, input de cantidad, input de serial (opcional, toggle si producto es serializado)
- Botón "Agregar item" (+)
- Botón "Quitar" por fila
- Stimulus controller `multi_item_form_controller.js` (nombre en snake_case, siguiendo convención del proyecto) para manejar filas dinámicas

#### Show (`show.html.twig`)
- Reemplazar sección de detalle único por tabla de items
- Columna: Producto, Cantidad, Serial, Estado, MovementId (si fulfilled)
- Botón "Despachar" por item si Request está APPROVED y el item está PENDING

---

## Migración de datos

Los registros existentes en `stock_requests` con producto único deben migrarse.

**⚠️ Importante:** No se puede usar SQL puro porque `RequestItemId` requiere un ULID válido de 26 caracteres. MySQL no tiene función nativa para generar ULIDs. Se debe usar una migración PHP de Doctrine Migrations:

```php
// Migración: Version20260610000001
public function up(Schema $schema): void
{
    // 1. Crear tabla stock_request_items primero (vía CREATE TABLE o schema)
    
    // 2. Migrar datos existentes
    $rows = $this->connection->fetchAllAssociative(
        "SELECT id, product_id, serialized_stock_id, quantity, status, fulfilled_movement_id FROM stock_requests"
    );
    
    foreach ($rows as $row) {
        $itemId = (string) \Symfony\Component\Uid\Ulid::generate(); // ULID válido
        $itemStatus = match ($row['status']) {
            'fulfilled' => 'fulfilled',
            'cancelled' => 'cancelled',
            default     => 'pending',
        };
        
        $this->connection->insert('stock_request_items', [
            'id'                   => $itemId,
            'request_id'           => $row['id'],
            'product_id'           => $row['product_id'],
            'serialized_stock_id'  => $row['serialized_stock_id'],
            'quantity'             => $row['quantity'],
            'status'               => $itemStatus,
            'fulfilled_movement_id' => $row['fulfilled_movement_id'],
        ]);
    }
    
    // 3. DROP columnas viejas
    $this->addSql('ALTER TABLE stock_requests 
        DROP COLUMN product_id,
        DROP COLUMN serialized_stock_id,
        DROP COLUMN quantity,
        DROP COLUMN fulfilled_movement_id');
}
```

**Casos especiales:**
- Requests con status `cancelled` → item status = `cancelled`
- Requests con status `rejected` → item status = `pending` (el rejection es a nivel request, item nunca se procesó)
- Requests con `fulfilled_movement_id` no nulo → item status = `fulfilled`

---

## Wiring

| Command/Query | Handler | Caller(s) |
|---|---|---|
| `SubmitRequestCommand` | `SubmitRequestHandler` | `StockRequestController::create()` |
| `ApproveRequestCommand` | `ApproveRequestHandler` | `StockRequestController::approve()` |
| `RejectRequestCommand` | `RejectRequestHandler` | `StockRequestController::reject()` |
| `CancelRequestCommand` | `CancelRequestHandler` | `StockRequestController::cancel()` |
| `FulfillRequestItemCommand` | `FulfillRequestItemHandler` | `StockRequestController::fulfillItem()` |
| `FindRequestByIdQuery` | `FindRequestByIdHandler` | `StockRequestController::show()` |
| `ListRequestsQuery` | `ListRequestsHandler` | `StockRequestController::list()` |

## Eventos

### Nuevos eventos

**`RequestItemFulfilled`** — cuando un item individual se despacha
```php
final readonly class RequestItemFulfilled
{
    public function __construct(
        public string $requestId,
        public string $requestReference,
        public string $itemId,
        public string $productId,
        public int $quantity,
        public string $movementId,
    ) {}
}
```

**`RequestItemCancelled`** — cuando un item individual se cancela
```php
final readonly class RequestItemCancelled
{
    public function __construct(
        public string $requestId,
        public string $requestReference,
        public string $itemId,
        public string $productId,
    ) {}
}
```

### Eventos existentes modificados

**`RequestFulfilled`** — se mantiene, pero ahora se publica solo cuando el **último** item pendiente se despacha (i.e., `derivedStatus()` = `FULFILLED`). Se publica desde `FulfillRequestItemHandler` después de llamar `fulfillItem()`, si `derivedStatus()` retorna `FULFILLED`.

**`RequestCancelled`** — se mantiene. Al cancelar el Request completo, también se publica `RequestItemCancelled` por cada item pendiente.

## Checklist de tareas

- [x] Domain: Agregar `RequestItemStatus` enum
- [x] Domain: Agregar `PARTIALLY_FULFILLED` a `RequestStatus`
- [x] Domain: Crear `RequestItem` entity
- [x] Domain: Modificar `Request` entity (quitar campos único-item, agregar Collection items y métodos delegados)
- [x] Domain: Crear `RequestItemIdValueObject`
- [x] Domain: Crear `RequestItemIdException`
- [x] Domain: Crear eventos `RequestItemFulfilled`, `RequestItemCancelled`
- [x] Application: Crear `RequestItemCommandInput` VO
- [x] Application: Modificar `SubmitRequestCommand` (items array)
- [x] Application: Modificar `SubmitRequestHandler` (crear items, iterar)
- [x] Application: Crear `FulfillRequestItemCommand`
- [x] Application: Crear `FulfillRequestItemHandler`
- [x] Application: Crear `RequestItemResponse`
- [x] Application: Modificar `RequestResponse` (quitar single product, agregar items[], statusLabel labels faltantes)
- [x] Application: Modificar `FindRequestByIdHandler` (resolver nombres de items + eager-load items)
- [x] Application: Modificar `CancelRequestHandler` (cascada a items + publish `RequestItemCancelled`)
- [x] Application: Modificar `AcceptByTechnicianHandler` (iterar items PENDING, fulfill cada uno)
- [x] Application: Eliminar `FulfillRequestHandler` (reemplazado por `FulfillRequestItemHandler`)
- [x] Infrastructure: Crear `RequestItem.orm.xml`
- [x] Infrastructure: Modificar `Request.orm.xml`
- [x] Infrastructure: Crear `RequestItemStatusEnumType`
- [x] Infrastructure: Crear `RequestItemIdType`
- [x] Infrastructure: Registrar tipos en `doctrine.yaml`
- [x] Infrastructure: Modificar `DoctrineRequestRepository` (agregar `findAllWithItems()` para N+1 prevention)
- [x] Presentation: Agregar ruta `inventory_request_fulfill_item`
- [x] Presentation: Agregar `StockRequestController::fulfillItem()`
- [x] Presentation: Agregar ruta y acción API `fulfillItem` en `ApiInventoryRequestController`
- [x] Presentation: Modificar `form.html.twig` (tabla dinámica + Stimulus)
- [x] Presentation: Modificar `show.html.twig` (tabla de items + botón despachar por item, remover botón "Despachar" global)
- [x] Presentation: Crear Stimulus controller `multi_item_form_controller.js`
- [x] Migration: Script PHP de migración de datos existentes con ULIDs válidos
- [x] Migration: DROP columnas viejas
- [x] Tests: Unitarios para `RequestItem`, `Request.modified` (derivedStatus, fulfillItem, cancelItem)
- [x] Tests: `FulfillRequestItemHandlerTest`
- [x] Tests: Actualizar `SubmitRequestHandlerTest` (items array)
- [x] Tests: Actualizar `CancelRequestHandlerTest` (cascada items)
- [x] Tests: Actualizar `AcceptByTechnicianHandlerTest` (items iteration)
- [x] Tests: Actualizar `FindRequestByIdHandlerTest`, `ListRequestsHandlerTest`
- [x] Tests: Eliminar `FulfillRequestHandlerTest` (o reemplazar por item tests)
