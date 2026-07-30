# Feature Specification: Cross-Boundary Context Integration

**Spec path**: `specs/009-cross-bc/spec.md`
**Created**: 2026-06-17
**Status**: Completado ~90%

---

## 1. Descripción General

Las interacciones entre Bounded Contexts en Orbis siguen reglas estrictas de DDD. Este spec documenta los puntos de integración y garantiza que solo IDs crucen entre BCs.

---

## 2. Reglas Cross-BC

| # | Regla | Descripción |
|---|-------|-------------|
| X‑01 | **Solo IDs entre BCs** | Nunca instanciar entidades de otro BC. Solo referencias por ID. |
| X‑02 | **Async para eventos cross-BC** | Usar Messenger async. Sync solo para intra-BC. |
| X‑03 | **Guards padre inactivo** | Si un cliente es desactivado, sus contactos y terminales también. |
| X‑04 | **Cascada desactivación** | Client INACTIVE → Contacts INACTIVE → Users deactivated. Employee INACTIVE → User deactivated. |

---

## 3. Mapa de Interacciones

| BC Origen | BC Destino | Tipo | Mecanismo |
|-----------|-----------|------|-----------|
| Client | Identity | Escritura | `ContactStatusCascadeHandler` (Messenger async) |
| Organization | Identity | Escritura | `EmployeeStatusCascadeHandler` (Messenger async) + `UserStatusCascadeOnEmployeeChangeListener` (sync) |
| Identity | Organization | Lectura | `CrossBcUserEmailResolver` busca Employee por userId |
| Identity | Client | Lectura | `CrossBcContactFinder` busca Contact por userId |
| Organization | Support | Lectura | `TerminationVerificationService` consulta tickets activos |
| Inventory | Organization | Lectura | `StockViewScopeResolver` consulta Employee.profile |
| Location | Client | Lectura | `cityId` en Site referencia Location |
| Location | Organization | Lectura | `cityIds` en ServiceZone referencia Location |

---

## 4. Domain Events Cross-BC

| Evento | BC Origen | Suscriptor | BC Destino | Sync/Async |
|--------|-----------|------------|-----------|------------|
| `ClientDeactivated` | Client | `ContactStatusCascadeHandler` | Identity | Async |
| `ClientReactivated` | Client | `ContactStatusCascadeHandler` | Identity | Async |
| `ContactDeactivated` | Client | `ContactStatusCascadeHandler` | Identity | Async |
| `ContactReactivated` | Client | `ContactStatusCascadeHandler` | Identity | Async |
| `EmployeeDeactivated` | Organization | `EmployeeStatusCascadeHandler` | Identity | Async |
| `EmployeeReactivated` | Organization | `EmployeeStatusCascadeHandler` | Identity | Async |
| `EmployeeDeleted` | Organization | `EmployeeStatusCascadeHandler` | Identity | Async |
| `SlaAboutToExpire` | Support | Pendiente | Notification | Async (futuro) |
| `SlaBreached` | Support | Pendiente | Notification | Async (futuro) |

---

## 5. Known Gaps

| # | Gap | Prioridad |
|---|-----|-----------|
| G‑01 | **Dual cascade**: Organization usa sync listener + async Messenger. Unificar a solo async. | Media |
| G‑02 | **SLA → Notification**: Eventos definidos pero sin subscriber en Notification BC | Media |
| G‑03 | **Tests de integración cross-BC**: Solo existe `ClientContactCascadeTest` | Alta |
