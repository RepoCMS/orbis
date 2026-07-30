# Changelog

## [Unreleased]

### Added

#### Support BC
- **REST API controllers**: New `ApiSupportTicketController` with 15 endpoints covering the full ticket lifecycle (list, create, assign, start journey, arrive site, arrive base, register finding, start waiting, start assistance, pause, resume, complete assistance, approve, cancel, reschedule).
- **Dashboard API**: New `ApiSupportDashboardController` exposing `GET /api/v1/support/dashboard/summary` for support KPIs.
- All endpoints follow `#[IsGranted]` permissions, `JsonResponse` envelope, `DomainException→422` error handling.

### Changed

#### Architecture — Shared Kernel
- **TransactionManagerInterface** (ADR-011): Created application-layer abstraction for transaction management. All Inventory handlers now depend on `TransactionManagerInterface` instead of `Doctrine\ORM\EntityManagerInterface`, eliminating framework leakage in Application layer.
- **Cross-BC Event Cascade** (ADR-012): `DeactivateClientHandler`, `ActivateClientHandler`, and `DeleteClientHandler` now publish domain events (`ClientDeactivated`, `ClientReactivated`, `ClientDeleted`). Cross-BC cascading (contact deactivation/reactivation/deletion, notifications) moved to `CrossBcDomainEventHandler` in Notification BC via async Messenger.
- **Table Prefixes** (ADR-013): Unified all Doctrine ORM mappings with `{bc}_` prefix — `identity_`, `client_`, `org_`, `support_`, `expense_`, `location_`, `notification_`. Inventory BC migration pending (currently uses mixed `stock_`, `serial_`, `inventory_` prefixes).

#### Infrastructure
- **Services.yaml**: Explicit binding for `TransactionManagerInterface → DoctrineTransactionManager`.
- **Doctrine mappings**: All ORM XML mappings consistently use BC-prefixed table names.

### Tests
- **Total**: 1629 test methods across 384 test files.
- **Per BC**:
  - Identity: 236 tests (55 files)
  - Client: 222 tests (53 files)
  - Organization: 239 tests (48 files)
  - Support: 271 tests (57 files)
  - Inventory: 393 tests (94 files)
  - Expense: 150 tests (42 files)
  - Location: 40 tests (13 files)
  - Notification: 75 tests (21 files)
  - Shared: 3 tests (1 file)

### Deprecated
- No deprecations in this session.

### Fixed
- No critical bugs in this session — the Support API controllers follow established patterns.
