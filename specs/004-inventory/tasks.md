# Tasks — Inventory (Stock, Products, Transfers, Repairs)

**Spec**: `specs/004-inventory/spec.md`
**Código**: `src/app/Inventory/`
**Tests**: 39 test files (Unit) — ~400 tests
**Estimado**: ~65% implementado

---

## Leyenda
- ✅ Completado
- ❌ Pendiente

---

## TASK-01: Domain Layer

### 1.1 Entities (12+)
- [x] `Product` — Catálogo de productos
- [x] `Location` — Ubicaciones físicas (bodega, mochila, laboratorio, custodia)
- [x] `SerializedStock` — Stock con tracking por serial
- [x] `NonserializedStock` — Stock por cantidad
- [x] `Movement` — Transferencias de stock
- [x] `Repair` — Reparaciones de partes
- [x] `Request`, `RequestItem` — Solicitudes de repuestos (multi-item)
- [x] `PurchaseOrder`, `PurchaseOrderItem` — Órdenes de compra
- [x] `Entry`, `EntryItem` — Ingresos de stock
- [x] `TechnicianLoan` — Préstamos a técnicos
- [x] `PhysicalCount`, `PhysicalCountItem` — Conteos físicos
- [x] `Transformation` — Transformaciones de producto
- [x] `StockMinimum` — Alertas de stock mínimo

### 1.2 Service Interfaces
- [x] `StockViewScopeResolver` — Scoping de stock por perfil
- [x] `MovementViewScopeResolver` — Scoping de movimientos por perfil
- [x] `TransferAuthorizationService` — Autorización de transferencias

### 1.3 Enums
- [x] `TrackingType`, `StockStatus`, `MovementType`, `MovementStatus`, `RequestStatus`, `RepairStatus`, `PurchaseOrderStatus`, `EntryStatus`, `PhysicalCountStatus`, `TechnicianLoanStatus`, `TransformationType`

---

## TASK-02: Application Layer

### 2.1 Command Handlers (57 total)
- [ ] **Request lifecycle**: SubmitRequest, ApproveRequest, RejectRequest, CancelRequest, FulfillRequestItem, FulfillRequest, AcceptByTechnician, RejectByTechnician (8)
- [ ] **Transfer lifecycle**: CreateTransfer, ConfirmTransfer, CancelTransfer, RejectTransfer (4)
- [ ] **Repair lifecycle**: AssignRepair, StartRepair, CompleteRepair, MarkRepairUnrepairable (4)
- [ ] **TechnicianLoan lifecycle**: CreateTechnicianLoan, ApproveTechnicianLoan, RejectTechnicianLoan, CancelTechnicianLoan (4)
- [ ] **PurchaseOrder**: Create, CreateWithItems, CreateFromSuggestion, Update, Cancel, AddItem (6)
- [ ] **Entry**: Create, Confirm, Cancel, AddItem, DeleteItem, Update (6)
- [ ] **PhysicalCount**: Start, Submit, Approve, Reject (4)
- [ ] **Location**: Create, CreateSubLocation, Update, UpdateSubLocation, Delete, DeleteSubLocation (6)
- [ ] **Product**: Add, Update, Delete (3)
- [ ] **Stock**: RegisterDirectStock, WriteOffNonserialized, RegisterConsumable, RemoveConsumable, RegisterEmergencyPartUsage (5)
- [ ] **Other**: RecordTransformation, CreateCustodyAssignment, ReturnFromCustody, AttachEvidence, RemoveEvidence, DefineStockMinimum, DeleteStockMinimum (7)

### 2.2 Query Handlers (29 total)
- [ ] **List**: Products, Locations, SubLocationsByLocation, StockAlerts, StockMinimums, Movements, Repairs, Requests, Entries, PurchaseOrders, TechnicianLoans, PhysicalCounts, CustodyAssignments, Transformations, RepairConsumables, RepairEvidences (16)
- [ ] **Find**: ProductById, LocationById, MovementById, RepairById, RequestById, PurchaseOrderById, EntryById, TransformationById (8)
- [ ] **Dashboard**: InventoryDashboardMetrics, StockByProduct, StockOverview, GeneratePurchaseSuggestions (4)
- [ ] **Cross-BC**: GetActiveTerminalAssignments (1)

---

## TASK-03: Infrastructure Layer

### 3.1 Doctrine Repositories
- [x] Todas las implementaciones de repositorios

### 3.2 ORM Mappings
- [x] Todos los .orm.xml para entidades del dominio

### 3.3 Custom DBAL Types
- [x] Todos los tipos ULID + Enum implementados

---

## TASK-04: Presentation Layer

### 4.1 API Controllers
- [ ] Api controllers para todos los recursos

### 4.2 Web Controllers
- [ ] Web controllers + Twig templates

---

## TASK-05: Tests

### 5.1 Unit Tests (39 test files, ~400 tests)

**Command Handler Tests (17 files):**
- [x] `AddEntryItemHandlerTest`
- [x] `AddProductHandlerTest`
- [x] `ApproveRequestHandlerTest`
- [x] `AssignRepairHandlerTest`
- [x] `CancelRequestHandlerTest`
- [x] `CancelTransferHandlerTest`
- [x] `ConfirmEntryHandlerTest`
- [x] `ConfirmTransferHandlerTest`
- [x] `CreateEntryHandlerTest`
- [x] `CancelEntryHandlerTest`
- [x] `CreateTechnicianLoanHandlerTest`
- [x] `ApproveTechnicianLoanHandlerTest`
- [x] `RejectTechnicianLoanHandlerTest`
- [x] `CancelTechnicianLoanHandlerTest`
- [x] `CreateTransferHandlerTest`
- [x] `DefineStockMinimumHandlerTest`
- [x] `DeleteStockMinimumHandlerTest`
- [x] `FulfillRequestItemHandlerTest`
- [x] `MarkRepairUnrepairableHandlerTest`
- [x] `RejectRequestHandlerTest`
- [x] `RejectTransferHandlerTest`
- [x] `StartRepairHandlerTest`
- [x] `SubmitRequestHandlerTest`
- [ ] ~33 handlers aún sin test

**Query Handler Tests (13 files):**
- [x] `FindLocationByIdHandlerTest`
- [x] `FindMovementByIdHandlerTest`
- [x] `FindProductByIdHandlerTest`
- [x] `FindRepairByIdHandlerTest`
- [x] `FindRequestByIdHandlerTest`
- [x] `ListLocationsHandlerTest`
- [x] `ListMovementsHandlerTest`
- [x] `ListProductsHandlerTest`
- [x] `ListRepairsHandlerTest`
- [x] `ListRequestsHandlerTest`
- [x] `ListStockAlertsHandlerTest`
- [x] `ListStockMinimumsHandlerTest`
- [x] `StockOverviewHandlerTest`
- [ ] ~16 queries aún sin test

**Domain Tests (6 files):**
- [x] `EntryTest`, `LocationTest`, `NonserializedStockTest`, `ProductTest`, `RepairTest`, `SerializedStockTest`, `StockMinimumTest`

**Integration Tests (1 file):**
- [x] `TransferLifecycleTest`

---

## TASK-06: Known Gaps

| # | Gap | Estado |
|---|-----|--------|
| G‑01 | Scoping: ViewScopeResolvers implementados, falta en algunos endpoints | ❌ Pendiente |
| G‑02 | Préstamos (TechnicianLoan): UI parcial, faltan flujos completos | ❌ Pendiente |
| G‑03 | Conteos físicos (PhysicalCount): CRUD básico, falta conciliación automática | ❌ Pendiente |
| G‑04 | Custodia: Implementación inicial vía controller, sin aggregate root formal | ❌ Pendiente |
| G‑05 | Dashboard KPIs: Solo InventoryDashboardMetrics. Faltan stock por ubicación, valor estimado | ❌ Pendiente |
| G‑06 | Tests: 39 archivos para ~438 PHP. ~49/57 command handlers y ~16/29 query handlers sin test | ⏳ En progreso (de 17 a 39 archivos en última ronda) |
