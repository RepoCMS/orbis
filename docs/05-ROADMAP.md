# Orbis — Roadmap de Construcción

> Mapa de ruta activo del proyecto. Registra el avance real y las decisiones tomadas.
>
> Última actualización: 2026-05-12

---

## Leyenda

- ✅ Completado
- ✅🧪 Completado + con tests
- 🟡 Parcial / En progreso
- ⚪ Pendiente
- ❌ Fuera de scope actual

---

## Resumen de Progreso

| BC | Domain | Application | Infrastructure | Presentation | Estado |
|---|---|---|---|---|---|
| **Identity** | ✅ | ✅ | ✅ | ✅ | **Funcional** (sin MFA config) |
| **Organization** | ✅🧪 | ✅ | ✅ | ✅ | **Completo** |
| **Client** | ✅🧪 | ✅ | ✅ | ✅ | **Completo** |
| **Location** | ✅ | ⚪ | ⚪ | ⚪ | Solo dominio |
| **Inventory** | ✅🧪 | ✅ | ✅ | ✅ | **Funcional** (sin StockMin UI, sin SerialHistory UI) |
| **Support** | ⚪ | ⚪ | ⚪ | ⚪ | No iniciado |
| **Shared** | ✅ | — | ✅ | ✅ | **Completo** |

---

## Fase 0 — Fundación (Shared Kernel)

| Tarea | Estado |
|---|---|
| Estructura base Symfony 8.0 | ✅ |
| `AbstractUlid` — base para todos los IDs | ✅ |
| `DomainException` / `ApplicationException` — excepciones base | ✅ |
| `StringNormalizer` — normalización de texto | ✅ |
| `TimestampableInterface` + `TimestampsSubscriber` (Doctrine listener) | ✅ |
| `DomainExceptionListener` — convierte excepciones a respuestas HTTP 400 | ✅ |
| Custom Doctrine types base (`AbstractUlidType`, `AbstractStringValueObjectType`, `AbstractStringEnumType`) | ✅ |
| Instalar y configurar Doctrine ORM (XML mappings, sin anotaciones) | ✅ |
| Configurar MySQL (Docker, `.env`) | ✅ |
| `HomeController` + layout base | ✅ |
| Instalar `lexik/jwt-authentication-bundle` | ⚪ |
| Instalar `nelmio/cors-bundle` | ⚪ |
| Infraestructura de Domain Events | ⚪ |

---

## Fase 1 — Identity BC

### Domain

| Tarea | Estado |
|---|---|
| `User` — credenciales, estados (UNVERIFIED/ACTIVE/INACTIVE), failedLoginAttempts, lockedUntil, softDelete | ✅🧪 |
| `Role` — assign/revoke permisos, estados ACTIVE/INACTIVE | ✅🧪 |
| `Permission` — clave `module.action`, descripción | ✅🧪 |
| `UserToken` — tokens temporales con hash SHA-256, eliminación física al consumir | ✅ |
| `AuthLog` — entidad inmutable de auditoría de accesos | ✅ |
| `UserMfaConfig` — configuración TOTP (secret cifrado, confirmación, isEnabled) | ⚪ |
| `UserMfaBackupCode` — códigos de respaldo de un solo uso | ⚪ |
| Value Objects: `UsernameVO`, `RawPasswordVO`, `HashedPasswordVO`, `RoleNameVO`, `PermissionKeyVO` | ✅ |
| Repository interfaces: `UserRepositoryInterface`, `RoleRepositoryInterface`, `PermissionRepositoryInterface` | ✅ |
| Repository interfaces: `UserTokenRepositoryInterface`, `AuthLogRepositoryInterface` | ✅ |
| Excepciones de dominio | ✅ |

### Application

| Tarea | Estado |
|---|---|
| `RegisterUser` + `ActivateUser` + `VerifyUser` + `VerifyUserByToken` | ✅ |
| `DeactivateUser` + `ReactivateUser` + `SoftDeleteUser` | ✅ |
| `ChangePassword` + `ChangeUsername` | ✅ |
| `AssignRoleToUser` | ✅ |
| `UnlockUser` + `RecordFailedLogin` | ✅ |
| `GenerateActivationToken` | ✅ |
| `CreateRole` + `ActivateRole` + `DeactivateRole` + `RenameRole` | ✅ |
| `AssignPermissionToRole` + `RevokePermissionFromRole` + `SyncRolePermissions` | ✅ |
| `DefinePermission` + `ChangePermissionDescription` | ✅ |
| `FindUserById` + `FindUserByUsername` + `ListUsers` | ✅ |
| `FindRoleById` + `FindRoleByName` + `ListRoles` | ✅ |
| `FindPermissionById` + `FindPermissionByKey` + `FindPermissionsByIds` + `ListPermissions` | ✅ |
| `ListAuthLogByUser` Query | ⚪ |
| `CreateEmployeeWithUser` — cross-BC con Organization | ⚪ |
| `InviteContactToPortal` — cross-BC con Client | ⚪ |

### Infrastructure

| Tarea | Estado |
|---|---|
| XML mappings: User, UserToken, AuthLog, Role, Permission | ✅ |
| XML mappings: UserMfaConfig, UserMfaBackupCode | ⚪ |
| Doctrine types: UserId, Username, HashedPassword, UserStatus, RoleId, RoleName, RoleStatus, PermissionId, PermissionKey, UserTokenId, UserTokenType, AuthLogId, AuthLogEvent | ✅ |
| Repositorios: DoctrineUserRepository, DoctrineRoleRepository, DoctrinePermissionRepository, DoctrineUserTokenRepository, DoctrineAuthLogRepository | ✅ |
| `NativePasswordHasher` (bcrypt cost 12) | ✅ |
| `SymfonyMailerAccountNotifier` — envío de emails de activación/reset | ✅ |
| `MfaSubscriber` — integración TOTP con `spomky-labs/otphp` | ✅ |
| Migraciones: users, roles, permissions, role_permissions, user_tokens, auth_logs | ✅ |
| Seeder de Roles y Permisos | ⚪ |

### Presentation

| Tarea | Estado |
|---|---|
| `LoginController` — Fase 1 (credenciales) + Fase 2 (TOTP) | ✅ |
| `MfaController` — configuración y verificación TOTP | ✅ |
| `VerificationController` — activación de cuenta por token email | ✅ |
| `UserController` — listado, creación, detalle, gestión de cuenta | ✅ |
| `RoleController` — listado, creación, detalle, activar/desactivar | ✅ |
| Templates (login, 2fa_check, 2fa_setup, verification, user/list+form+show, role/list+form+show) | ✅ |
| Security Voters para autorización por permiso (`user.create`, etc.) | ⚪ |
| JWT endpoints para API REST | ⚪ |

---

## Fase 2 — Client BC

### Domain

| Tarea | Estado |
|---|---|
| `Client` — name, RUC ecuatoriano (dígito verificador), address, ACTIVE/INACTIVE, softDelete | ✅🧪 |
| `Contact` — firstName, lastName, email, phoneNumber, role, userId?, ACTIVE/INACTIVE, softDelete | ✅🧪 |
| `Catalogue` — inventario de modelos ATM por cliente (vendor, type, functionality, serial) | ✅ |
| `Terminal` — instancia de ATM desplegada (idAsignedByClient, name, catalogueId) | ✅ |
| `Site` — sucursal/ubicación del cliente (name, address) | ✅ |
| Value Objects: ClientNameVO, RucVO, ContactEmailVO, ContactPhoneVO, ContactRoleVO, etc. | ✅ |
| Enums: ClientStatus, ContactStatus, ContactRole, CatalogueStatus, TerminalVendor, TerminalType | ✅ |
| Repository interfaces | ✅ |
| Excepciones | ✅ |

### Application

| Tarea | Estado |
|---|---|
| `RegisterClient` + `UpdateClient` + `DeactivateClient` + `DeleteClient` | ✅ |
| `AddContact` + `DeactivateContact` + `ReactivateContact` + `SoftDeleteContact` | ✅ |
| `LinkUserToContact` | ✅ |
| `AddToCatalogue` | ✅ |
| Comandos para Terminal y Site | 🟡 |
| `ListClients` + `FindClientById` | ✅🧪 |
| `FindContactsByClient` | ✅ |
| Queries para Catalogue, Terminal, Site | 🟡 |

### Infrastructure

| Tarea | Estado |
|---|---|
| XML mappings: Client, Contact, Catalogue, Terminal | ✅ |
| Doctrine types (11 tipos) | ✅ |
| Repositorios: DoctrineClientRepository, DoctrineContactRepository, DoctrineCatalogueRepository | ✅ |
| Migraciones: clients, contacts, catalogues, terminals | ✅ |

### Presentation

| Tarea | Estado |
|---|---|
| `ClientController` — list, create, show, edit, activate, deactivate, delete | ✅ |
| `ContactController` — nested bajo `/clients/{clientId}/contacts` | ✅ |
| Templates (client/list+form+show, contact/form) | ✅ |

---

## Fase 3 — Organization BC

### Domain

| Tarea | Estado |
|---|---|
| `Employee` — identificationNumber, firstName, lastName, email, phoneNumber, userId?, hiredAt, terminatedAt, ACTIVE/INACTIVE, softDelete | ✅🧪 |
| Value Objects: EmployeeId, EmployeeEmail, EmployeeIdentificationNumber | ✅🧪 |
| Repository interface `EmployeeRepositoryInterface` | ✅ |
| Excepciones | ✅ |

### Application

| Tarea | Estado |
|---|---|
| `CreateEmployee` + `UpdateEmployee` + `DeactivateEmployee` + `ReactivateEmployee` + `SoftDeleteEmployee` | ✅ |
| `LinkUserToEmployee` | ✅ |
| `FindEmployeeById` + `ListEmployees` | ✅ |

### Infrastructure

| Tarea | Estado |
|---|---|
| XML mapping: Employee | ✅ |
| Doctrine types (4 tipos) | ✅ |
| `DoctrineEmployeeRepository` | ✅ |
| Migración: employees (con created_at, updated_at) | ✅ |

### Presentation

| Tarea | Estado |
|---|---|
| `EmployeeController` — list, create, show, edit | ✅ |
| Templates (employee/list+form+show) | ✅ |

---

## Fase 4 — Location BC (datos de referencia)

> Datos geográficos de Ecuador — Region, Province, City. Son datos de solo lectura, se poblan con un seeder.

| Tarea | Estado |
|---|---|
| `Region`, `Province`, `City` entities + VOs + Repository interfaces + Exceptions | ✅ |
| XML mappings y Doctrine repositories | ⚪ |
| Migración y seeder de datos geográficos de Ecuador | ⚪ |
| Query para listar ciudades/provincias (usado en formularios) | ⚪ |

---

## Fase 5 — Seguridad y API REST

> Pendiente. Prerrequisito para la app móvil.

### JWT + CORS

| Tarea | Estado |
|---|---|
| Instalar `lexik/jwt-authentication-bundle` + `nelmio/cors-bundle` | ⚪ |
| Generar par de claves RSA para JWT | ⚪ |
| `AuthController` API — login fase 1+2, refresh token, logout | ⚪ |
| Refresh Token endpoint | ⚪ |
| Security Voters para autorización granular por permiso | ⚪ |

### API Controllers (v1)

| Tarea | Estado |
|---|---|
| `UserController` API `/api/v1/users` | ⚪ |
| `RoleController` API + `PermissionController` API | ⚪ |
| `EmployeeController` API `/api/v1/employees` | ⚪ |
| `ClientController` API + `ContactController` API | ⚪ |
| Manejo global de errores (`ExceptionListener`) | ⚪ |
| Documentación OpenAPI / Swagger | ⚪ |

---

## Fase 6 — Support BC (Tickets)

> BC de mayor complejidad. Depende de Identity, Client, Inventory, Location.

| Tarea | Estado |
|---|---|
| Diseño del modelo de dominio (`Ticket`, `Journey`, `Finding`, `Solution`, etc.) | ⚪ |
| Ciclo de vida del ticket (estados, transiciones, guards) | ⚪ |
| Application layer completa (Commands + Queries) | ⚪ |
| Infrastructure (Doctrine mappings, repositorios, migraciones) | ⚪ |
| Presentation web + API | ⚪ |

---

## Fase 7 — Inventory BC

> Gestión de repuestos, almacenes, laboratorios, reparaciones y solicitudes de movimiento.
>
> **Estado actual (2026-05-12):** Domain + Infrastructure + Application + Presentation completos. `StockMinimum` y `SerialHistory` con infra lista; UI pendiente.

### Domain

#### Enums ✅
| Tarea | Estado |
|---|---|
| `LocationType` — WAREHOUSE, LABORATORY, BACKPACK | ✅ |
| `SerializedStockStatus` — AVAILABLE, RESERVED, DAMAGED, DECOMMISSIONED | ✅ |
| `NonserializedStockStatus` — AVAILABLE, RESERVED, DAMAGED, DECOMMISSIONED | ✅ |
| `PurchaseOrderStatus` — OPEN, PARTIAL, COMPLETED, CANCELLED | ✅ |
| `EntryStatus` — DRAFT, CONFIRMED, CANCELLED | ✅ |
| `RepairStatus` — PENDING, IN_PROGRESS, COMPLETED, UNREPAIRABLE | ✅ |
| `MovementType` — ENTRY, TRANSFER, EXIT | ✅ |
| `MovementStatus` — PENDING, IN_TRANSIT, COMPLETED, CANCELLED | ✅ |
| `RequestStatus` — PENDING, APPROVED, AWAITING_TECHNICIAN_ACCEPTANCE, REJECTED, FULFILLED, CANCELLED | ✅ |
| `SerialHistoryReason` — REPAIR, TRANSFORMATION | ✅ |

#### Entities ✅🧪 (parcial)
| Tarea | Estado |
|---|---|
| `Product` — catálogo con código, nombre, `trackingType` (serialized/non_serialized) | ✅🧪 |
| `Location` — almacén/laboratorio/mochila con LocationType | ✅🧪 |
| `SerializedStock` — unidad individual con serial y ciclo de vida AVAILABLE→RESERVED→etc. | ✅🧪 |
| `NonserializedStock` — lote de repuesto fungible con status (unicidad: product+location+status) | ✅🧪 |
| `Movement` — registro de desplazamiento de stock entre ubicaciones | ✅ |
| `PurchaseOrder` — orden de compra a proveedor (createdBy) | ✅ |
| `PurchaseOrderItem` — línea de orden de compra con quantityOrdered/quantityReceived | ✅ |
| `Entry` — recepción de mercancía (DRAFT→CONFIRMED/CANCELLED, createdBy) | ✅🧪 |
| `EntryItem` — línea de recepción con serial opcional y ubicación destino | ✅ |
| `Repair` — reparación de repuesto serializado dañado (technicianId como auditoría) | ✅🧪 |
| `Transformation` — canje de repuesto dañado por nuevo | ✅ |
| `Request` — solicitud de movimiento con aprobación + aceptación técnico | ✅ |
| `StockMinimum` — umbral mínimo por producto+ubicación con alertThreshold | ✅ |
| `SerialHistory` — trazabilidad de cambios de serial (REPAIR/TRANSFORMATION) | ✅ |
| Eliminar `Laboratory.php` stub (redundante — Location type=LABORATORY lo cubre) | ⚪ |

#### Value Objects ✅
| Tarea | Estado |
|---|---|
| `ProductIdValueObject`, `ProductCodeAssignedBySupplierValueObject`, `ProductNameValueObject` | ✅ |
| `LocationIdValueObject`, `LocationNameValueObject` | ✅ |
| `SerializedStockIdValueObject`, `StockSerialNumberValueObject` | ✅ |
| `NonserializedStockIdValueObject` | ✅ |
| `MovementIdValueObject` | ✅ |
| `PurchaseOrderIdValueObject`, `PurchaseOrderItemIdValueObject` | ✅ |
| `EntryIdValueObject`, `EntryItemIdValueObject` | ✅ |
| `RepairIdValueObject` | ✅ |
| `TransformationIdValueObject` | ✅ |
| `RequestIdValueObject` | ✅ |
| `StockMinimumIdValueObject` | ✅ |
| `SerialHistoryIdValueObject` | ✅ |

#### Repository Interfaces
| Tarea | Estado |
|---|---|
| `ProductRepositoryInterface` | ✅ |
| `LocationRepositoryInterface` | ✅ |
| `SerializedStockRepositoryInterface` | ✅ |
| `NonserializedStockRepositoryInterface` | ✅ |
| `MovementRepositoryInterface` | ✅ |
| `PurchaseOrderRepositoryInterface`, `PurchaseOrderItemRepositoryInterface` | ✅ |
| `EntryRepositoryInterface`, `EntryItemRepositoryInterface` | ✅ |
| `RepairRepositoryInterface` | ✅ |
| `TransformationRepositoryInterface` | ✅ |
| `RequestRepositoryInterface` | ✅ |
| `StockMinimumRepositoryInterface` | ✅ |
| `SerialHistoryRepositoryInterface` | ✅ |

#### Excepciones de dominio ✅
| Tarea | Estado |
|---|---|
| `InsufficientStockException` | ✅ |
| `StockReservedException` | ✅ |
| `EntryAlreadyConfirmedException` | ✅ |
| `StockMinimumIdException`, `SerialHistoryIdException` | ✅ |

### Infrastructure ✅

#### Doctrine Types (18/18)
| Tarea | Estado |
|---|---|
| `ProductIdType`, `ProductCodeType`, `ProductNameType` | ✅ |
| `LocationIdType`, `LocationNameType` | ✅ |
| `SerializedStockIdType`, `StockSerialNumberType` | ✅ |
| `NonserializedStockIdType` | ✅ |
| `MovementIdType` | ✅ |
| `PurchaseOrderIdType`, `PurchaseOrderItemIdType` | ✅ |
| `EntryIdType`, `EntryItemIdType` | ✅ |
| `RepairIdType` | ✅ |
| `TransformationIdType` | ✅ |
| `RequestIdType` | ✅ |
| `StockMinimumIdType` | ✅ |
| `SerialHistoryIdType` | ✅ |

#### ORM XML Mappings (14/14)
| Tarea | Estado |
|---|---|
| `Product.orm.xml` (table `products`) | ✅ |
| `Location.orm.xml` (table `stock_locations`) | ✅ |
| `SerializedStock.orm.xml` (table `serialized_stock`) | ✅ |
| `NonserializedStock.orm.xml` (table `nonserialized_stock`) | ✅ |
| `Movement.orm.xml` (table `stock_movements`) | ✅ |
| `PurchaseOrder.orm.xml` (table `purchase_orders`) | ✅ |
| `PurchaseOrderItem.orm.xml` (table `purchase_order_items`) | ✅ |
| `Entry.orm.xml` (table `inventory_entries`) | ✅ |
| `EntryItem.orm.xml` (table `inventory_entry_items`) | ✅ |
| `Repair.orm.xml` (table `repairs`) | ✅ |
| `Transformation.orm.xml` (table `stock_transformations`) | ✅ |
| `Request.orm.xml` (table `stock_requests`) | ✅ |
| `StockMinimum.orm.xml` (table `stock_minimums`, unique: product+location) | ✅ |
| `SerialHistory.orm.xml` (table `serial_history`) | ✅ |

#### Doctrine Repositories (14/14)
| Tarea | Estado |
|---|---|
| `DoctrineProductRepository` | ✅ |
| `DoctrineLocationRepository` | ✅ |
| `DoctrineSerializedStockRepository` | ✅ |
| `DoctrineNonserializedStockRepository` | ✅ |
| `DoctrineMovementRepository` | ✅ |
| `DoctrinePurchaseOrderRepository`, `DoctrinePurchaseOrderItemRepository` | ✅ |
| `DoctrineEntryRepository`, `DoctrineEntryItemRepository` | ✅ |
| `DoctrineRepairRepository` | ✅ |
| `DoctrineTransformationRepository` | ✅ |
| `DoctrineRequestRepository` | ✅ |
| `DoctrineStockMinimumRepository` | ✅ |
| `DoctrineSerialHistoryRepository` | ✅ |

#### Migraciones
| Tarea | Estado |
|---|---|
| Phase 1: `products`, `stock_locations`, `serialized_stock`, `nonserialized_stock`, `stock_movements` | ✅ |
| Phase 2: `purchase_orders`, `purchase_order_items`, `inventory_entries`, `inventory_entry_items`, `repairs`, `stock_transformations` | ✅ |
| Phase 3: `nonserialized_stock` — drop reserved_quantity, add status, new unique index | ✅ |
| Phase 4: `stock_requests` | ✅ |
| Phase 5: `products` add `tracking_type` column | ✅ |
| Phase 6: `stock_minimums`, `serial_history` tables | ✅ |

#### config/doctrine.yaml y services.yaml ✅
| Tarea | Estado |
|---|---|
| 18 Doctrine types registrados | ✅ |
| 14 repository bindings en services.yaml | ✅ |

### Unit Tests ✅🧪 (parcial)
| Tarea | Estado |
|---|---|
| `ProductTest` — 4 tests | ✅ |
| `LocationTest` — 5 tests | ✅ |
| `SerializedStockTest` — 9 tests | ✅ |
| `NonserializedStockTest` — 16 tests | ✅ |
| `EntryTest` — 6 tests | ✅ |
| `RepairTest` — 5 tests | ✅ |
| `RequestTest` — ciclo de vida completo con AWAITING_TECHNICIAN_ACCEPTANCE | ⚪ |
| `StockMinimumTest` | ⚪ |
| `SerialHistoryTest` | ⚪ |

### Application Layer ✅

#### Commands — Product
| Tarea | Estado |
|---|---|
| `AddProductCommand` + Handler | ✅ |
| `UpdateProductCommand` + Handler (con guard de trackingType si hay stock) | ✅ |
| `DeleteProductCommand` + Handler | ✅ |

#### Commands — Location
| Tarea | Estado |
|---|---|
| `CreateLocationCommand` + Handler | ✅ |
| `UpdateLocationCommand` + Handler | ✅ |
| `DeleteLocationCommand` + Handler | ✅ |

#### Commands — Purchase Order
| Tarea | Estado |
|---|---|
| `CreatePurchaseOrderCommand` + Handler | ✅ |
| `AddPurchaseOrderItemCommand` + Handler | ✅ |
| `CancelPurchaseOrderCommand` + Handler | ✅ |

#### Commands — Entry (Recepción de Mercancía)
| Tarea | Estado |
|---|---|
| `CreateEntryCommand` + Handler | ✅ |
| `AddEntryItemCommand` + Handler | ✅ |
| `ConfirmEntryCommand` + Handler | ✅ |
| `CancelEntryCommand` + Handler | ✅ |

#### Commands — Stock
| Tarea | Estado |
|---|---|
| `RegisterDirectStockCommand` + Handler | ✅ |
| `WriteOffNonserializedStockCommand` + Handler | ✅ |

#### Commands — Request (Solicitud de Movimiento)
| Tarea | Estado |
|---|---|
| `SubmitRequestCommand` + Handler | ✅ |
| `ApproveRequestCommand` + Handler | ✅ |
| `RejectRequestCommand` + Handler | ✅ |
| `CancelRequestCommand` + Handler | ✅ |
| `FulfillRequestCommand` + Handler | ✅ |
| `NotifyTechnicianAcceptanceCommand` + Handler | ⚪ |
| `AcceptByTechnicianCommand` + Handler | ⚪ |
| `RejectByTechnicianCommand` + Handler | ⚪ |

#### Commands — Repair
| Tarea | Estado |
|---|---|
| `AssignRepairCommand` + Handler | ✅ |
| `StartRepairCommand` + Handler | ✅ |
| `CompleteRepairCommand` + Handler (+ registrar SerialHistory si cambia serial) | ✅ |
| `MarkRepairUnrepairableCommand` + Handler | ✅ |

#### Commands — Transformation
| Tarea | Estado |
|---|---|
| `RecordTransformationCommand` + Handler (+ registrar SerialHistory) | ✅ |

#### Queries
| Tarea | Estado |
|---|---|
| `FindProductById` + `ListProducts` | ✅ |
| `FindLocationById` + `ListLocations` | ✅ |
| `StockByProduct` (serialized + nonserialized) | ✅ |
| `FindPurchaseOrderById` + `ListPurchaseOrders` | ✅ |
| `FindEntryById` + `ListEntries` | ✅ |
| `FindRepairById` + `ListRepairs` | ✅ |
| `FindRequestById` + `ListRequests` | ✅ |
| `FindMovementById` + `ListMovements` | ✅ |
| `FindTransformationById` + `ListTransformations` | ✅ |
| `ListStockMinimums` + dashboard de alertas | ⚪ |
| `SerialHistoryByStock` — trazabilidad completa de un serial | ⚪ |

### Presentation Layer ✅

#### Web Controllers + Templates
| Tarea | Estado |
|---|---|
| `ProductController` — list, create, show, edit (trackingType editable si sin stock), delete | ✅ |
| `LocationController` — list, create, show, edit, delete | ✅ |
| `PurchaseOrderController` — list, create, show, add items, cancel | ✅ |
| `EntryController` — list, create, show, add items, confirm, cancel | ✅ |
| `RepairController` — list, create, show, start, complete, mark-unrepairable | ✅ |
| `StockRequestController` — list, submit, show, approve/reject, cancel | ✅ |
| `MovementController` — list, show (solo lectura) | ✅ |
| `TransformationController` — list, record, show | ✅ |
| `StockMinimumController` — dashboard de alertas, CRUD de umbrales | ⚪ |

#### API REST Controllers (v1)
| Tarea | Estado |
|---|---|
| `ProductController` API | ⚪ |
| `StockController` API | ⚪ |
| `RequestController` API (app móvil) | ⚪ |
| `RepairController` API | ⚪ |

---

## Fases Futuras (post-MVP)

| Fase | Descripción |
|---|---|
| Expense BC | Gastos operativos relacionados a incidencias |
| Notification BC | Email + WhatsApp (reemplazar notificaciones simples actuales) |
| Reporting | Dashboards y reportes de gestión |
| App móvil | React Native / Flutter — consume la API REST |

---

## Registro de Sesiones

### Sesión 2026-05-12
**Inventory BC — Completar domain gaps y desbloquear trackingType.**
- **trackingType editable en Product:** añadido `changeTrackingType()` en dominio con guard en Application Layer que rechaza el cambio si hay stock registrado. Formulario de edición muestra radios editables (sin stock) o badge bloqueado (con stock).
- **ProductHasStockException** creada para comunicar el rechazo al controlador con mensaje claro.
- **UpdateProductCommand** ahora acepta `trackingType?: string` opcional.
- **RequestStatus AWAITING_TECHNICIAN_ACCEPTANCE:** nuevo estado añadido al enum. Request entity tiene `notifyTechnicianAcceptance()`, `acceptByTechnician()`, `rejectByTechnician()`. `fulfill()` acepta desde APPROVED o AWAITING.
- **StockMinimum entity + infra:** entidad con `idealQuantity`, `alertThreshold`, guard `alertThreshold ≤ idealQuantity`, `isBelowThreshold(int)`. Repo, type, ORM XML, migración ejecutada.
- **SerialHistory entity + infra:** entidad inmutable con `reason: SerialHistoryReason` (REPAIR/TRANSFORMATION), `previousSerialNumber → newSerialNumber`. Repo, type, ORM XML, migración ejecutada.
- **SerialHistoryReason enum** creado (REPAIR, TRANSFORMATION).
- **Migraciones ejecutadas:** Version20260512140758 (tracking_type DEFAULT fix) + Version20260512143310 (stock_minimums, serial_history).
- **Schema validado:** `[OK] The database schema is in sync with the mapping files.`
- **Pendientes próximos:** Commands para `NotifyTechnicianAcceptance`/`AcceptByTechnician`/`RejectByTechnician`, `StockMinimumController` (dashboard alertas), `SerialHistoryQuery`, unit tests para Request/StockMinimum/SerialHistory.

### Sesión 2026-05-06
**Inventory BC — Domain e Infrastructure completados. Diseño de entidad Request.**
- **Domain phase 1 (sesiones anteriores):** Product, Location, SerializedStock, NonserializedStock, Movement + enums + VOs + repo interfaces + unit tests (45 tests, 82 assertions).
- **Domain phase 2 (esta sesión):** PurchaseOrder, PurchaseOrderItem, Entry, EntryItem, Repair, Transformation + sus enums/VOs/repo interfaces.
- **NonserializedStock redesign:** eliminado `reservedQuantity`, añadido `status: NonserializedStockStatus`. Unicidad `(product_id, location_id, status)` — permite coexistir AVAILABLE + RESERVED + DAMAGED del mismo producto en la misma ubicación.
- **Infrastructure completa:** 15 Doctrine types, 11 ORM XML mappings, 11 Doctrine repositories, services.yaml bindings, doctrine.yaml registros. Schema `[OK]` en sync.
- **Migraciones ejecutadas** para todas las tablas de Inventory BC.
- **Decisión de auditoría:** campos de auditoría en las entidades del dominio (`createdBy`, `confirmedBy`, `cancelledBy`, `approvedBy`, etc. con `UserIdValueObject`). Sin tabla de audit log separada para el MVP de Inventory. `AuthLog` permanece solo para Identity (eventos de seguridad).
- **Diseño de entidad `Request`:** solicitud de movimiento con flujo PENDING→APPROVED→FULFILLED / PENDING→REJECTED / PENDING→CANCELLED. Pendiente de implementar.
- **Eliminación pendiente:** `Laboratory.php` stub (es redundante — `Location` con `type=LABORATORY` lo cubre).
- **Próximos pasos:** implementar `Request` entity + infra, luego Application Layer (commands + queries), luego Presentation.

### Sesión 2026-05-04
**Revisión completa del estado del proyecto contra el código real:**
- Identity BC: prácticamente completo. UserMfaConfig/UserMfaBackupCode entities aún pendientes.
- Organization BC: completo con lifecycle de Employee, 6 commands, 2 queries, tests de entidad y VOs.
- Client BC: más avanzado que lo documentado. Incluye además de Client/Contact:
  - `Catalogue` — catálogo de modelos ATM por cliente (NCR, Diebold, Wincor, etc.)
  - `Terminal` — instancia de ATM desplegada (vincula a catálogo)
  - `Site` — sucursal/ubicación del cliente
- Location BC: solo dominio implementado (Region, Province, City + VOs + repo interfaces), sin infra ni app.
- Inventory BC: carpeta existe con 11 archivos PHP vacíos (stubs sin implementar).
- Support BC: carpeta existe con 7 archivos PHP vacíos (stubs sin implementar).
- spomky-labs/otphp instalado, MfaSubscriber implementado en Identity Infrastructure.
- JWT (lexik) y CORS (nelmio) NO instalados aún.
- `TimestampsSubscriber` y `DomainExceptionListener` en Shared — funcionando.
- 10 archivos de tests unitarios: Identity (UserTest, RoleTest, PermissionTest), Organization (EmployeeTest + 3 VO tests), Client (ClientTest, ContactTest, ListClientsHandlerTest).

### Sesión 2026-04-21
- Decidido mantener `failedLoginAttempts` + `lockedUntil` en `User` (no tabla separada) — performance en auth
- `UserToken`: eliminación física en lugar de `usedAt`. Job de limpieza para expirados.
- `Client`: `ClientNameVO` ampliado a 100 chars, `RucVO` con validación dígito verificador completa
- `CreateClientCommand` incluye primer Contact obligatoriamente
- `Client.softDelete()`: cascada coordinada por Application Service
- `Contact`: `ContactPhoneNumberVO` (10 dígitos, inicia 09), `ContactRoleVO`, `deactivate()` desactiva User en Identity BC
- Timestamps: `deletedAt` en dominio (es regla de negocio), `createdAt`/`updatedAt` en infraestructura (Doctrine)

---

## Glosario Rápido

| Término | Definición |
|---|---|
| **Bounded Context** | Límite explícito donde un modelo de dominio es válido y consistente |
| **Aggregate Root** | Entidad principal que controla acceso a todo su agregado |
| **Value Object** | Objeto inmutable definido por su valor, no por identidad |
| **ULID** | Identificador único lexicográficamente ordenable (alternativa a UUID) |
| **CQRS** | Separación de Commands (escritura) y Queries (lectura) |
| **Soft Delete** | Marcar registro como eliminado sin borrarlo físicamente (`deleted_at`) |
| **JWT** | Token firmado stateless para autenticación en APIs REST |
| **TOTP** | Contraseña de un solo uso basada en tiempo (Google Authenticator) |
| **Data Mapper** | Patrón donde BD y dominio están completamente separados (Doctrine) |

---

## Deuda Técnica Activa

> Problemas conocidos que deben resolverse antes de continuar con nuevas funcionalidades. En orden de prioridad.

### [CRÍTICO] Migración: `created_at`/`updated_at` faltantes en tabla `clients`

El ORM XML de `Client` mapea `createdAt` y `updatedAt` pero la tabla no tiene esas columnas.

**Error en producción:** `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'c0_.created_at'`

**Solución:** Crear migración:
```sql
ALTER TABLE clients ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME DEFAULT NULL;
```

> Verificar también tablas `contacts`, `catalogues`, `terminals`, `sites` por el mismo problema.

---

### [ALTA] Crear `UserMfaBackupCode` (REQ-ID-001c)

- Nueva entidad `UserMfaBackupCode` (id, userId, codeHash, usedAt)
- ORM XML + migration + repositorio + Application Commands
- `AuthLog` ya tiene el valor `MFA_BACKUP_CODE_USED` — solo falta la entidad

---

### [ALTA] Reglas de consistencia de estado cross-BC (REQ-CST-001 a REQ-CST-004)

Domain Events + Event Listeners pendientes para:

| Regla | Evento a publicar | Listener a crear |
|---|---|---|
| REQ-CST-001 | `EmployeeDeactivated`, `EmployeeDeleted`, `EmployeeReactivated` | Desactiva/reactiva `User` |
| REQ-CST-002 | `ClientDeactivated`, `ClientDeleted` | Desactiva contacts en cadena → Users |
| REQ-CST-003 | `ContactDeactivated`, `ContactDeleted`, `ContactReactivated` | Desactiva/reactiva `User` |
| REQ-CST-004 | — | Guards en handlers: padre inactivo/eliminado bloquea creación de dependientes |

---

### [ALTA] Flujo A: `CreateEmployeeWithUserCommand` (REQ-ID-002)

Application Service coordinado que crea `User` (Identity) + `Employee` (Organization) en una sola transacción. Si falla la creación del User → rollback completo.

Actualmente los comandos `RegisterUser` y `CreateEmployee` son independientes.

---

### [ALTA] Flujo B: `InviteContactToPortalCommand` (REQ-ID-002)

Application Service coordinado que crea `User` (Identity) + vincula a `Contact` (Client). El usuario recibe email de activación.

---

### [MEDIA] `Site.orm.xml` falta (REQ-CL)

`Site` tiene entidad de dominio y mapeo ORM en services.yaml, pero el archivo `Site.orm.xml` no existe. Causa error al consultar Sites.

---

### [MEDIA] Location BC — infrastructure completa (REQ-LOC)

Solo el Domain Layer está implementado. Pendiente:
- ORM XML para `Region`, `Province`, `City`
- Migration + seeder con datos geográficos de Ecuador
- Repositorios Doctrine
- Presentación / select dinámico en formularios de Site

---

### [MEDIA] Inventory BC — entidad `Request` completa

`Request` (solicitud de movimiento) está diseñada pero no implementada:
- Domain: entity + VO + repo interface + excepciones + enum `RequestStatus`
- Infrastructure: ORM XML + Doctrine type + Doctrine repository + migration
- Tests: `RequestTest`
- Application Layer completa (Commands + Queries)

---

### [BAJA] Decidir gestión dinámica de Roles

Existen comandos `CreateRole`, `RenameRole`, `ActivateRole`, `DeactivateRole`.
Los requerimientos mencionan roles administrables desde UI.

**Pendiente:** Confirmar si la UI expondrá CRUD completo de roles o solo el seeder inicial.

---

### [BAJA] Security Voters para autorización granular

Los endpoints web y API deben validar permisos específicos (`user.create`, `ticket.assign`, etc.) usando Symfony Security Voters.

---

### Bugs Corregidos (referencia histórica)

| Sesión | Entidad | Bug | Corrección |
|---|---|---|---|
| 2026-05-04 | `User` | Faltaban `softDelete()`, `isDeleted()`, `deletedAt()` | Añadidos |
| 2026-05-04 | `Contact` | Faltaban `softDelete()`, `isDeleted()`, `deletedAt()` | Añadidos |
| 2026-05-04 | `Employee` | Faltaban `softDelete()`, `isDeleted()`, `deletedAt()` | Añadidos |
| 2026-05-04 | `Client` | Faltaban `softDelete()`, `isDeleted()`, `deletedAt()` | Añadidos |
| 2026-05-04 | `Catalogue` | Faltaba campo `deletedAt` en entidad (ORM XML lo tenía) | Añadidos |
| 2026-05-04 | `Terminal` | Faltaban `deletedAt` + métodos soft delete (ORM XML los tenía) | Añadidos |
| 2026-05-04 | `Site` | Faltaban `deletedAt` + métodos soft delete | Añadidos |
| 2026-05-04 | `DoctrineCatalogueRepository` | `delete()` usaba `Contact::class` en lugar de `Catalogue::class` | Corregido |
| 2026-05-04 | `User` | `email` y `totpSecret` eliminados del dominio. `UserEmailResolverInterface` + `CrossBcUserEmailResolver` resuelven email cross-BC | Refactorizado |

---

## Continuidad del Trabajo

### Memoria Persistente
- Guardar el estado del proyecto, reglas clave y preferencias del usuario.
- Cargar automáticamente la memoria al iniciar una nueva sesión.

### Flujo de Trabajo
1. **Inicio de Sesión**: Revisar la memoria para retomar el trabajo desde el último punto.
2. **Documentación**: Actualizar el roadmap y guardar un resumen después de cada tarea.
3. **Tareas Pendientes**: Mantener una lista actualizada de tareas pendientes.

---
