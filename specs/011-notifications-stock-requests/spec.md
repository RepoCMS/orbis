# Feature Specification: Notifications for Stock Requests

**Spec path**: `specs/011-notifications-stock-requests/spec.md`
**Created**: 2026-06-17
**Updated**: 2026-07-30
**Status**: Implementado (100%)

---

## 1. Descripción General

Enviar notificaciones automáticas cuando se realizan solicitudes de repuestos: al bodeguero cuando un técnico solicita un repuesto, y al técnico cuando el repuesto está disponible.

---

## 2. Business Rules

| # | Regla | Estado |
|---|-------|--------|
| NS‑01 | Notificar al bodeguero cuando se crea una solicitud de repuesto | ✅ |
| NS‑02 | Notificar al técnico cuando su solicitud es despachada | ✅ |
| NS‑03 | Usar Notification BC con canal in-app + email | ✅ |

---

## 3. Implementación

### Domain Events (Inventory BC)
- `RequestSubmitted` — 6 propiedades (requestId, reference, requestedByUserId, productId, quantity, sourceLocationId)
- `RequestRejected`, `RequestApproved`, `RequestCancelled`, `RequestFulfilled`

### Event Publishing
- `SubmitRequestHandler` → publica `RequestSubmitted`

### Cross-BC Notification Wiring
- `CrossBcDomainEventHandler` mapea:
  - `RequestSubmitted` → notifica a `sourceResponsibleUserId` + `requestedByUserId` con `EventType::REQUEST_SUBMITTED`
  - `RequestRejected` → notifica a `requestedByUserId` con `EventType::REQUEST_REJECTED`
  - `RequestApproved` → notifica con `EventType::REQUEST_APPROVED`

### Notification Templates
- Seedeadas via `SeedNotificationTemplatesCommand`:
  - `REQUEST_SUBMITTED` (IN_APP + EMAIL)
  - `REQUEST_AWAITING_TECHNICIAN_ACCEPTANCE`
  - `REQUEST_REJECTED`, `REQUEST_CANCELLED`, `REQUEST_FULFILLED`

### Tests
- `CrossBcDomainEventHandlerTest` — verifica flujo RequestSubmitted → Notification, RequestRejected → Notification
