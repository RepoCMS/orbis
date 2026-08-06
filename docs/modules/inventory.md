# Module: Inventory

## Overview

The **Inventory** Bounded Context manages product tracking, stock levels, purchase orders, requests, transfers, and repair workflows within the Orbis platform. It integrates deeply with Support (spare parts), Location (storage), and Organization (responsible personnel).

## Core Responsibilities

- Product catalog management
- Stock tracking (serialized and non-serialized)
- Purchase order lifecycle
- Stock request/approval workflow
- Internal transfers between locations
- Repair management for technician loans
- Physical inventory counts
- Stock minimum thresholds and alerts

## Status

- **Progress:** ~95%
- **Tests:** ~96 files
- **Reports:** ValuedInventory, Movement, ResignationReturn, PhysicalCount
- **Importers:** Productos, Stock serializado, Stock no serializado, Órdenes de Compra
- **Dashboard:** API-only

## Domain Model

### Aggregate: Product

| Field | Type | Description |
|-------|------|-------------|
| `id` | `ProductIdValueObject` (ULID) | Unique identifier |
| `name` | `ProductNameValueObject` | Product name |
| `type` | `ProductType` | `serialized` / `non_serialized` |
| `isConsumable` | `bool` | Is consumable item? |

### Aggregate: Stock

Tracks available quantity per location, with transaction logging for all movements.

### Aggregate: PurchaseOrder

Manages procurement from suppliers with line items, approval workflow, and receiving.

### Aggregate: Entry

Represents inward goods (purchases, returns, adjustments) with status tracking.

### Aggregate: Request

Internal stock requests with approval workflow.

### Aggregate: Transfer

Inter-location transfers with confirmation tracking.

### Aggregate: Repair

Manages technician equipment repair with loan/return lifecycle.

### Aggregate: PhysicalCount

Cycle count and full physical inventory management.

## Commands (Key Subset)

- `AddProduct` — Create new product
- `UpdateProduct` — Update product details
- `DeleteProduct` — Delete product
- `ImportProducts` — Import product catalog
- `RegisterDirectStock` — Direct stock registration
- `RegisterConsumable` — Register consumable usage
- `ImportSerializedStock` — Import serialized stock
- `ImportNonserializedStock` — Import non-serialized stock
- `CreatePurchaseOrder` — Create PO
- `CreatePurchaseOrderFromSuggestion` — Auto-sourced PO
- `UpdatePurchaseOrder` — Update PO
- `ApprovePhysicalCount` — Approve count
- `SubmitRequest` — Submit stock request
- `ApproveRequest` — Approve request
- `CreateTransfer` — Initiate transfer
- `ConfirmTransfer` — Confirm transfer received
- `StartRepair` — Begin repair process
- `CompleteRepair` — Complete repair
- `AssignRepair` — Assign to technician
- `RegisterEmergencyPartUsage` — Record emergency part usage

## Queries

- `ListProducts` — Paginated product list
- `FindProductById` — Find product
- `StockOverview` — Stock levels summary
- `StockByProduct` — Stock for specific product
- `ListPurchaseOrders` — PO list with filters
- `FindPurchaseOrderById` — Find PO
- `FindProductsByPurchaseOrder` — Products in PO
- `ListEntries` — Entries list
- `FindEntryById` — Find entry
- `GeneratePurchaseSuggestions` — Auto-suggestions
- `ListStockAlerts` — Low stock alerts
- `ListStockMinimums` — Minimum thresholds
- `ListRequests` — Requests with workflow status
- `ListTransfers` — Transfers with status
- `ListRepairs` — Repair workflow
- `GetTicketDetail` — Repair details (cross-BC)
- `InventoryDashboardMetrics` — Dashboard KPIs

## Cross-BC Integrations

| BC | Direction | Interaction |
|----|-----------|-------------|
| Support | Read | Repair management needs ticket context |
| Support | Write | Emergency parts usage |
| Location | Read | Storage location hierarchies |
| Organization | Read | Responsible personnel |
| Shared Kernel | Both | Import/export, dashboard metrics |

## Importers

1. **Import Productos** — Product catalog CSV/Excel
2. **Import Stock serializado** — Serialized stock items
3. **Import Stock no serializado** — Non-serialized stock items
4. **Import Órdenes de Compra** — Purchase order CSV import

## Reports

1. **ValuedInventory** — Current inventory valuation
2. **Movement** — Transaction history
3. **ResignationReturn** — Items returned on employee termination
4. **PhysicalCount** — Count comparison report

## Pending Gaps

| Gap | Priority |
|-----|----------|
| Web dashboard (currently API-only) | Medium |
| Missing test coverage | Low |
| Complete spec alignment | Low |

## API Endpoints

| Method | Route | Permission |
|--------|-------|------------|
| GET | `/api/v1/inventory/products` | `products.view` |
| POST | `/api/v1/inventory/products` | `products.create` |
| GET | `/api/v1/inventory/purchase-orders` | `purchase_orders.view` |
| POST | `/api/v1/inventory/purchase-orders` | `purchase_orders.create` |
| GET | `/api/v1/inventory/entries` | `entries.view` |
| GET | `/api/v1/inventory/dashboard` | `inventory.view_dashboard` |

*(Full API endpoint list in spec)*