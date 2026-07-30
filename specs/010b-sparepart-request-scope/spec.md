# Feature Specification: SparePart Request Scope

**Spec path**: `specs/010b-sparepart-request-scope/spec.md`
**Created**: 2026-06-17
**Updated**: 2026-07-30
**Status**: Implementado (~95%)

---

## 1. Descripción General

Controlar el alcance de las solicitudes de repuestos: un técnico solo puede solicitar repuestos para tickets que tiene asignados, y solo desde su mochila (ubicación de inventario).

---

## 2. Business Rules

| # | Regla | Estado |
|---|-------|--------|
| SP‑01 | Técnico solo solicita repuestos para tickets donde está asignado | ✅ |
| SP‑02 | Los repuestos solicitados se reservan de la mochila del técnico | ✅ |
| SP‑03 | Un supervisor puede solicitar para cualquier ticket de su zona | ✅ |

---

## 3. Implementación

### Application Layer
- `SparePartViewScopeResolver` — resuelve scope según perfil del empleado
- `SparePartScope` — DTO con `employeeId` y `locationId`
- Inyectado en `RequestPendingSparePartHandler` y `RegisterSparePartHandler`
- Configurado en `services.yaml` (autowire binding)

### Comportamiento
- **Técnico**: scope resuelve su `locationId` (mochila). Solo ve/usa stock de su mochila.
- **No técnico** (supervisor/admin): scope = null (sin restricción).

### Tests
- `SparePartViewScopeResolverTest` (4 tests)
- `RequestPendingSparePartHandlerTest` — verifica scoping
- `RegisterSparePartHandlerTest` — verifica scoping

---

## 4. Pendiente (~5%)

- Scoping en queries de listado de spare parts (actualmente solo en commands de creación)
