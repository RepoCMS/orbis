# Feature Specification: Employee-User Invariant

**Spec path**: `specs/010a-employee-user-invariant/spec.md`
**Created**: 2026-06-17
**Updated**: 2026-07-30
**Status**: Implementado (~95%)

---

## 1. Descripción General

Garantizar que la relación entre Employee (Organization BC) y User (Identity BC) se mantenga consistente: un empleado puede tener 0 o 1 usuario vinculado, y un usuario puede estar vinculado a 0 o 1 empleado. No pueden existir huérfanos ni duplicados.

---

## 2. Business Rules

| # | Regla | Estado |
|---|-------|--------|
| EI‑01 | Un Employee puede tener 0 o 1 User vinculado | ✅ |
| EI‑02 | Un User puede tener 0 o 1 Employee vinculado | ✅ |
| EI‑03 | Al desvincular, el User queda huérfano → detectar y notificar | ✅ |
| EI‑04 | Al eliminar Employee, desvincular User automáticamente | ✅ |
| EI‑05 | Comando `identity:detect-orphan-users` con scheduler nocturno | ✅ |

---

## 3. Implementación

### Domain Layer
- `Employee::linkUser()`, `unlinkUser()`, `hasUser()`, `userId()` — Organization BC
- `User::linkEmployee()`, `unlinkEmployee()`, `hasEmployeeLinked()` — Identity BC
- Eventos: `EmployeeUserLinked`, `EmployeeUserUnlinked`

### Application Layer
- `LinkUserToEmployeeCommand` + `LinkUserToEmployeeHandler`
- `UnlinkUserFromEmployeeCommand` + `UnlinkUserFromEmployeeHandler`
- `EmployeeUserInvariantService` — validación cross-BC (no double-link, no duplicados)

### Scheduler / Console
- `DetectOrphanUsersCommand` (CLI: `identity:detect-orphan-users`)
- `DetectOrphanUsersMessage` + `DetectOrphanUsersMessageHandler` (async)
- `OrphanUserChecker` — detecta empleados sin user y viceversa
- Schedule: `0 3 * * *` via Symfony Scheduler

### Web/API
- `EmployeeController::linkUser()`, `unlinkUser()` — rutas web
- `ApiOrganizationController::linkEmployeeUser()`, `unlinkEmployeeUser()` — rutas API

### Tests
- `EmployeeUserInvariantTest` (6 tests)
- `LinkUserToEmployeeHandlerTest` (5 tests)
- `UnlinkUserFromEmployeeHandlerTest` (4 tests)

---

## 4. Pendiente (~5%)

- Stimulus modal para linking/unlinking desde UI de Employee
- Notificación cross-BC cuando se detectan huérfanos (actualmente solo log)
