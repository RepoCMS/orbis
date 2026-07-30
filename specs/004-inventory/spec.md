# Feature Specification: Inventory Management

**Spec path**: `specs/004-inventory/spec.md`
**Created**: 2026-06-17
**Updated**: 2026-07-30
**Status**: Implementado (~95%)

---

## 1. Descripción General

El Bounded Context **Inventory** gestiona todo el inventario de repuestos y equipos de ACMS. Es el BC más grande del sistema (60 commands, 29 queries, 96 test files).

Responsabilidades:
- Catálogo de productos (con tracking type: serializado / no serializado)
- Stock serializado (partes con número de serie individual)
- Stock no serializado (partes por cantidad, con lotes)
- Transferencias de stock entre ubicaciones (reserve+split/completeTransfer)
- Reparaciones de partes (mantiene serial original)
- Transformaciones (cambia código de producto y/o serial)
- Órdenes de compra y entradas de inventario
- Solicitudes de repuestos multi-item con fulfill parcial
- Préstamos a técnicos (TechnicianLoan)
- Custodia de equipos
- Conteos físicos (PhysicalCount)
- Alertas de stock mínimo
- Mochilas auto-creadas por técnico
- Reportes: Inventario Valorizado, Movimientos de Stock (XLSX/PDF)
- Importers: Productos, Stock Serializado, Stock No Serializado, Órdenes de Compra

---

## 2. Business Rules

| # | Regla | Descripción |
|---|-------|-------------|
| IN‑01 | **Tracking type fijo** | Una vez que un producto tiene stock, no se puede cambiar su tracking type. |
| IN‑02 | **Serial único** | Cada serialized stock tiene un serial único en el sistema. |
| IN‑03 | **Mochila auto-creada** | Al crear un técnico, se auto-crea una ubicación tipo `backpack` para su stock. |
| IN‑04 | **Transfer reserve+split** | Non-serialized: al transferir, se reduce el origen y se crea batch RESERVED. Al confirmar, se elimina RESERVED y se agrega al destino. |
| IN‑05 | **Ownership de transfers** | CancelBy → solo creador. ConfirmBy/RejectBy → solo destinatario. |
| IN‑06 | **Reparación mantiene serial** | No se permite cambiar serial al reparar (los bancos identifican seriales no originales). |
| IN‑07 | **Transformación** | Solo productos marcados `isTransformable=true`. Puede cambiar código y/o serial. |
| IN‑08 | **Multi-item request** | Cada item tiene estado propio (PENDING/FULFILLED/CANCELLED). Fulfill parcial permitido. |
| IN‑09 | **PENDING_REVIEW** | Non-serialized sospechoso sale del cajero en estado PENDING_REVIEW. |
| IN‑10 | **Scoping** | Técnicos solo ven stock de su mochila y transfers donde son creador/destinatario. |

---

## 3. Aggregates Principales

| Entidad | Tipo | Descripción |
|---------|------|-------------|
| `Product` | AR | Catálogo de productos |
| `Location` | AR | Ubicaciones (bodegas, mochilas) |
| `SerializedStock` | Entity | Stock con serial individual |
| `NonserializedStock` | Entity | Stock por cantidad/lote |
| `Movement` | AR | Transferencias de stock |
| `Repair` | AR | Reparaciones |
| `Request` | AR | Solicitudes multi-item |
| `RequestItem` | Entity | Item individual de solicitud |
| `PurchaseOrder` | AR | Órdenes de compra |
| `Entry` | AR | Entradas de inventario |
| `EntryItem` | Entity | Item de entrada |
| `TechnicianLoan` | AR | Préstamos a técnicos |
| `PhysicalCount` | AR | Conteos físicos |
| `Transformation` | AR | Transformaciones |
| `CustodyAssignment` | AR | Custodia de equipos |

---

## 4. Commands (60)

| Área | Comandos |
|------|----------|
| **Producto** | `AddProduct`, `UpdateProduct`, `DeleteProduct` |
| **Ubicaciones** | `CreateLocation`, `UpdateLocation`, `DeleteLocation`, `CreateSubLocation`, `UpdateSubLocation`, `DeleteSubLocation` |
| **Transferencias** | `CreateTransfer`, `ConfirmTransfer`, `CancelTransfer`, `RejectTransfer` |
| **Reparaciones** | `StartRepair`, `CompleteRepair`, `AssignRepair`, `MarkRepairUnrepairable`, `AttachEvidence`, `RemoveEvidence`, `RegisterConsumable`, `RemoveConsumable`, `RegisterEmergencyPartUsage` |
| **Transformaciones** | `RecordTransformation` |
| **Solicitudes** | `SubmitRequest`, `ApproveRequest`, `RejectRequest`, `CancelRequest`, `FulfillRequestItem`, `AcceptByTechnician`, `RejectByTechnician` |
| **Órdenes de Compra** | `CreatePurchaseOrder`, `CreatePurchaseOrderWithItems`, `CreatePurchaseOrderFromSuggestion`, `AddPurchaseOrderItem`, `UpdatePurchaseOrder`, `CancelPurchaseOrder` |
| **Entradas** | `CreateEntry`, `UpdateEntry`, `ConfirmEntry`, `CancelEntry`, `AddEntryItem`, `DeleteEntryItem` |
| **Stock Directo** | `RegisterDirectStock` |
| **Stock Mínimo** | `DefineStockMinimum`, `DeleteStockMinimum` |
| **Préstamos** | `CreateTechnicianLoan`, `ApproveTechnicianLoan`, `CancelTechnicianLoan`, `RejectTechnicianLoan` |
| **Conteos** | `StartPhysicalCount`, `SubmitPhysicalCount`, `ApprovePhysicalCount`, `RejectPhysicalCount` |
| **Custodia** | `CreateCustodyAssignment`, `ReturnFromCustody` |
| **Importers** | `ImportProducts`, `ImportSerializedStock`, `ImportNonserializedStock`, `ImportPurchaseOrders` |
| **Otros** | `WriteOffNonserializedStock` |

---

## 5. Queries (29)

| Área | Queries |
|------|---------|
| **Producto** | `ListProducts`, `FindProductById`, `StockByProduct`, `StockOverview` |
| **Ubicaciones** | `ListLocations`, `FindLocationById`, `ListSubLocationsByLocation` |
| **Movimientos** | `ListMovements`, `FindMovementById` |
| **Reparaciones** | `ListRepairs`, `FindRepairById`, `ListRepairConsumables`, `ListRepairEvidences` |
| **Solicitudes** | `ListRequests`, `FindRequestById` |
| **Órdenes de Compra** | `ListPurchaseOrders`, `FindPurchaseOrderById`, `FindProductsByPurchaseOrder`, `GeneratePurchaseSuggestions` |
| **Entradas** | `ListEntries`, `FindEntryById` |
| **Stock** | `ListStockAlerts`, `ListStockMinimums` |
| **Préstamos** | `ListTechnicianLoans` |
| **Conteos** | `ListPhysicalCounts` |
| **Transformaciones** | `ListTransformations`, `FindTransformationById` |
| **Custodia** | `ListCustodyAssignments` |
| **Dashboard** | `InventoryDashboardMetrics` |

---

## 6. Transfer Lifecycle

```
PENDING ──startTransit()──► IN_TRANSIT ──confirmBy()──► COMPLETED
                                │                           │
                                ├──cancelBy()──► CANCELLED   │
                                └──rejectBy()──► REJECTED    │
                                                             │
                                  (old code) ────────────────┘
```

---

## 7. Cross-BC

| BC | Interacción |
|----|-------------|
| **Organization** | `StockViewScopeResolver` busca Employee.profile para scoping |
| **Organization** | `MovementViewScopeResolver` filtra por creador/destinatario |
| **Client** | Terminal assignments referenciadas en repairs |
| **Support** | Repuestos usados en tickets |
| **Notification** | Eventos de solicitudes → notificaciones vía CrossBcDomainEventHandler |

---

## 8. Reportes & Importers

| Tipo | Recurso | Formato | Estado |
|------|---------|---------|--------|
| Reporte | Inventario Valorizado | XLSX/PDF | ✅ |
| Reporte | Movimientos de Stock | XLSX/PDF | ✅ |
| Importer | Productos | CSV/Excel | ✅ |
| Importer | Stock Serializado | CSV/Excel | ✅ |
| Importer | Stock No Serializado | CSV/Excel | ✅ |
| Importer | Órdenes de Compra | CSV/Excel | ✅ |

---

## 9. Known Gaps

| # | Gap | Prioridad |
|---|-----|-----------|
| G‑01 | **Scoping**: StockViewScopeResolver + MovementViewScopeResolver implementados, falta en algunos endpoints | Media |
| G‑02 | **Préstamos (TechnicianLoan)**: UI parcial, faltan flujos completos | Media |
| G‑03 | **Conteos físicos (PhysicalCount)**: CRUD básico, falta conciliación automática | Media |
| G‑04 | **Custodia**: Implementación inicial, faltan flujos | Baja |
| G‑05 | **Dashboard KPIs**: Faltan stock por ubicación, valor estimado, etc. | Media |
| G‑06 | **Tests**: 96 test files. Coverage aceptable pero faltan algunos handlers de import | Media |
