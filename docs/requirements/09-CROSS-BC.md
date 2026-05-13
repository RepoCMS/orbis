# Reglas Cross-BC, Consistencia de Estado y Requerimientos No Funcionales

Este documento define las reglas que garantizan coherencia cuando una entidad cambia de estado o es eliminada lógicamente, y los requisitos no funcionales del sistema.

---

## Reglas de Consistencia de Estado (REQ-CST)

Se implementan mediante **Domain Events** publicados por los handlers de Application y escuchados por Event Listeners de Infrastructure.

---

### REQ-CST-001: Cascada de Desactivación — Employee → User

Cuando un `Employee` es **desactivado** (`terminate()`):
- Si tiene `userId` vinculado → `User.deactivate()` (cross-BC a Identity)
- Si el `User` ya estaba `INACTIVE` → no se hace nada (idempotente)

Cuando un `Employee` es **eliminado lógicamente** (`softDelete()`):
- Si tiene `userId` → `User.softDelete()` (cross-BC a Identity)

Cuando un `Employee` es **reactivado** (`reactivate()`):
- Si tiene `userId` y el `User` está `INACTIVE` → `User.reactivate()` (cross-BC a Identity)

**Mecanismo:**
- `DeactivateEmployeeHandler` → publica `EmployeeDeactivated`
- `SoftDeleteEmployeeHandler` → publica `EmployeeDeleted`
- `ReactivateEmployeeHandler` → publica `EmployeeReactivated`
- Listeners en Identity BC reaccionan a estos eventos

---

### REQ-CST-002: Cascada de Desactivación — Client → Contact → User

Cuando un `Client` es **desactivado**:
- Todos sus `Contact`s activos → `Contact.deactivate()`
- Si esos contactos tienen `userId` → `User.deactivate()` (cross-BC a Identity)

Cuando un `Client` es **eliminado lógicamente**:
- Todos sus `Contact`s → `Contact.softDelete()`
- Si esos contactos tienen `userId` → `User.softDelete()` (cross-BC a Identity)

Cuando un `Client` es **reactivado**:
- Sus `Contact`s **no se reactivan automáticamente** — el operador los reactiva individualmente

**Mecanismo:**
- `DeactivateClientHandler` → publica `ClientDeactivated`
- `DeleteClientHandler` → publica `ClientDeleted`
- Listeners en Client BC y Identity BC reaccionan en cadena

---

### REQ-CST-003: Cascada de Desactivación — Contact → User

Cuando un `Contact` es **desactivado**:
- Si tiene `userId` → `User.deactivate()` (cross-BC a Identity)

Cuando un `Contact` es **eliminado lógicamente**:
- Si tiene `userId` → `User.softDelete()` (cross-BC a Identity)

Cuando un `Contact` es **reactivado**:
- Si tiene `userId` y el `User` está `INACTIVE` → `User.reactivate()` (cross-BC a Identity)

**Mecanismo:**
- `DeactivateContactHandler` → publica `ContactDeactivated`
- `SoftDeleteContactHandler` → publica `ContactDeleted`
- `ReactivateContactHandler` → publica `ContactReactivated`

---

### REQ-CST-004: Guards de Creación — Entidad Inactiva Bloquea Operaciones

Las siguientes operaciones deben ser **rechazadas** si la entidad padre está inactiva o eliminada lógicamente:

| Entidad padre inactiva / eliminada | Operación bloqueada |
|---|---|
| `Client` INACTIVE o `deletedAt != null` | Crear `Contact` para ese cliente |
| `Client` INACTIVE o `deletedAt != null` | Crear `Site` para ese cliente |
| `Client` INACTIVE o `deletedAt != null` | Crear `Terminal` / `TerminalAssignment` para ese cliente |
| `Client` INACTIVE o `deletedAt != null` | Crear ticket de Support para ese cliente |
| `Client` INACTIVE o `deletedAt != null` | Crear `Contract` para ese cliente |
| `Client` INACTIVE o `deletedAt != null` | Crear `ClientSaleOrder` para ese cliente |
| `Contact` INACTIVE o `deletedAt != null` | Vincular o crear `User` para ese contacto |
| `Employee` INACTIVE o `deletedAt != null` | Vincular o crear `User` para ese empleado |

**Implementación:** Guards en Application Layer (handlers), antes de ejecutar la operación. Lanzar `DomainException` con mensaje descriptivo.

---

### REQ-CST-005: Guards de Login — User Inactivo no puede Autenticarse

- `User` con `status = INACTIVE` → no puede hacer login (manejado por Symfony Security)
- `User` con `deletedAt != null` → no puede hacer login (manejado por el `UserProvider`)

**Estado:** ✅ Ya implementado

---

## Diagrama de Relaciones Cross-BC

```
┌──────────────────────────────────────────────────────┐
│                   IDENTITY BC                        │
│   User ──────► Role ──────► Permission[]             │
│   (username, password, status, roleId)               │
│   UserToken | UserMfaConfig | AuthLog                │
└──────────────────────────────────────────────────────┘
         ▲                 ▲
         │ userId?         │ userId?
         │                 │
┌────────┴─────┐    ┌──────┴─────────┐
│ ORGANIZATION │    │   CLIENT BC    │
│   Employee   │    │ Client→Contact │
└──────────────┘    │ Site→Terminal  │
         ▲          │ CSZ→Contract   │
         │          └────────────────┘
         │ employeeId          ▲ clientId + terminalId
         │                     │
┌────────┴────────────────────┴──────┐
│              SUPPORT BC            │
│  Ticket → TicketTechnician         │
│  Journey | Finding | Evidence      │
│  TicketSparePart | TicketPause     │
└────────────────────────────────────┘
         ▲ ticketId?     ▲ productCode
         │               │
┌────────┴───┐    ┌──────┴──────────┐
│  EXPENSE   │    │  INVENTORY BC   │
│ OpExpense  │    │ Product→Stock   │
│ CashAdvance│    │ Request→Movement│
│ AfterHour  │    │ Entry→Repair    │
└────────────┘    └─────────────────┘

┌──────────────────────────────────────────────────────┐
│               GEOGRAPHY / LOCATION BC                │
│        Region → Province → City                      │
│   (usado por: Client.Site, Client.CSZ, Inventory.Loc)│
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│              NOTIFICATION BC (transversal)           │
│  Escucha Domain Events de todos los BCs              │
│  NotificationPreference | Template | Log             │
└──────────────────────────────────────────────────────┘
```

**Regla fundamental:** Los BCs se comunican **solo por referencia de ID (ULID)**. Nunca se instancian entidades de otro BC dentro de un BC.

---

## Requerimientos No Funcionales

### RNF-001: Performance
- La API debe responder en menos de 500ms para consultas simples
- Paginación obligatoria para listados (máximo 100 registros por página)
- Índices de BD en columnas de filtro frecuente (`status`, `deleted_at`, `client_id`, etc.)

### RNF-002: Seguridad
- Autenticación JWT en todos los endpoints de API REST (excepto login)
- Autenticación por sesión Symfony para la aplicación web
- MFA TOTP obligatorio para todos los usuarios
- Autorización basada en permisos granulares (`módulo.acción`)
- Validación de datos de entrada en todos los endpoints (formularios web + API)
- Prevención de inyección SQL / XSS
- Hash bcrypt (cost 12) para contraseñas
- Tokens de seguridad almacenados solo como hash SHA-256

### RNF-003: Documentación API
- Todos los endpoints documentados en Markdown (ver `docs/`)
- Códigos de respuesta HTTP estándar (200, 201, 400, 401, 403, 404, 422, 500)

### RNF-004: Testing
- Cobertura mínima de tests unitarios en Domain Layer: 80%
- Tests unitarios para: Value Objects, Entities (métodos + guards), Handlers críticos
- Tests de integración para Application Services con efectos cross-BC

### RNF-005: Logging y Auditoría
- `AuthLog` en Identity BC para todos los eventos de seguridad
- Campos de auditoría (`createdBy`, `approvedBy`, etc.) en entidades de dominio de Inventory, Support, Expense
- Soft delete (`deleted_at`) en todo aggregate/entidad persistida (excepto tablas de auditoría inmutable y tablas pivot)

### RNF-006: Versionamiento de API
- API versionada desde el inicio: `/api/v1/{resource}`
- Nunca romper compatibilidad en la misma versión

### RNF-007: Identificadores
- Todos los IDs son **ULID** generados en el dominio (nunca auto-increment de BD)
- Los ULIDs son lexicográficamente ordenables (ventaja sobre UUID para paginación por cursor)

### RNF-008: Soft Delete
- Toda entidad persistida con ciclo de vida debe tener `deleted_at`
- **Excepciones** (sin soft delete): `AuthLog`, `NotificationLog`, tablas pivot/join (`role_permissions`), `UserToken`
- Un registro con `deleted_at != null` no aparece en ningún listado ni puede ser referenciado en nuevas operaciones

### RNF-009: Responsive Design
- La UI web debe ser completamente adaptable a cualquier tamaño de pantalla
- Mobile-first considerando que coordinadores pueden acceder desde dispositivos móviles

### RNF-010: API-First
- Toda funcionalidad expuesta como API REST para ser consumida por la futura app móvil
- Web y API REST comparten la misma Application Layer (Commands + Queries)
