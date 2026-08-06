# Orbis — Convenciones de Código y Arquitectura

> **Propósito:** Convenciones de nomenclatura, estructura de carpetas, contratos API, excepciones y patrones arquitectónicos de Orbis. Derivado de `CONTEXT.md` §8/§9, `AGENTS.md` (Arquitectura — Referencia Rápida, Transfer Lifecycle, Apéndice D) y verificación contra código real.
>
> **Última verificación:** 2026-08-06

---

## 1. Nomenclatura (CONTEXT.md §8)

| Elemento | Patrón | Ejemplo |
|----------|--------|---------|
| Command | `{Action}{Resource}Command` | `CreateUserCommand` |
| Handler | `{Action}{Resource}Handler` | `CreateUserHandler` |
| Query | `{Find\|List}{Resource}Query` | `ListUsersQuery` |
| QueryHandler | `{Find\|List}{Resource}Handler` | `ListUsersHandler` |
| Response DTO | `{Resource}Response` | `UserResponse` |
| Controller (API) | `Api{BC}{Entity}Controller` | `ApiIdentityUserController` |
| Controller (Web) | `{BC}{Entity}Controller` | `IdentityDashboardController` |
| Report Generator | `{Nombre}ReportGenerator` | `ContractsExpiringReportGenerator` |
| Importer | `Import{Resource}Handler` | `ImportClientHandler` |
| DBAL Type | `{FieldName}Type` | `UserTokenTypeType` |
| Enum | `{Name}Enum` o `{Name}Status` | `UserStatus`, `TicketStatus` |
| ValueObject | `{Name}ValueObject` | `UserIdValueObject` |
| ViewScopeResolver | `{Resource}ViewScopeResolver` | `StockViewScopeResolver` |

### Ejemplos verificados en el repo (2026-08-06)

| Elemento | Ejemplo real |
|----------|--------------|
| Command + Handler | `src/app/Identity/Application/Command/CreateRole/CreateRoleCommand.php` + `CreateRoleHandler.php` |
| Query + Handler | `src/app/Identity/Application/Query/ListUsers/ListUsersQuery.php` + `ListUsersHandler.php` |
| Response | `src/app/Inventory/Application/Query/Response/ProductResponse.php` (23 Responses en Inventory) |
| Controller API | `src/app/Identity/Presentation/Http/ApiIdentityUserController.php` (también `ApiIdentityAuthController`, `ApiIdentityMfaController`) |
| Report Generator | `src/app/Client/Application/Report/ContractsExpiring/ContractsExpiringReportGenerator.php` |
| Importer | Importers reales: Clientes, Terminales, Modelos (Client); Productos, Stock serializado, Stock no serializado, Órdenes de Compra (Inventory); Empleados (Organization); Usuarios (Identity); Gastos Operativos (Expense); Partes de Modelo (Client) — 11 total |
| DBAL Type | `src/app/Identity/Infrastructure/Doctrine/Types/UserTokenTypeType.php` (extends `AbstractStringEnumType`) |
| Enum | `UserTokenType` (Identity), `TicketStatus` (Support), `EmployeeStatus` ACTIVE/ON_VACATION/INACTIVE (Organization) |
| ValueObject | `UserIdValueObject`, `RoleIdValueObject`, `RoleNameValueObject` (Identity) |
| ViewScopeResolver | `src/app/Inventory/Application/Service/StockViewScopeResolver.php`, `MovementViewScopeResolver.php`; `src/app/Support/Application/Service/SparePartViewScopeResolver.php` |

---

## 2. Estructura de Directorios por BC (CONTEXT.md §9)

```
src/app/{BC}/
├── Application/
│   ├── Command/{Action}{Resource}/
│   │   ├── {Action}{Resource}Command.php
│   │   └── {Action}{Resource}Handler.php
│   ├── Query/{Find|List}{Resource}/
│   │   ├── {Find|List}{Resource}Query.php
│   │   └── {Find|List}{Resource}Handler.php
│   ├── Query/Response/{Resource}Response.php
│   ├── Service/{Resource}ViewScopeResolver.php  (si aplica)
│   └── Report/{Nombre}ReportGenerator.php       (si aplica)
├── Domain/
│   ├── Entities/{Entity}.php
│   ├── ValueObjects/{Name}ValueObject.php
│   ├── Enums/{Name}Enum.php
│   ├── Repositories/{Resource}RepositoryInterface.php
│   ├── Events/{EventName}.php
│   └── Exceptions/{BC}DomainException.php
├── Infrastructure/
│   ├── Persistence/Doctrine/
│   │   ├── Doctrine{Resource}Repository.php
│   │   └── Mapping/{Entity}.orm.xml
│   ├── Doctrine/Types/{Field}Type.php
│   └── Service/{IntegrationService}.php
└── Presentation/
    ├── Http/
    │   ├── Api{BC}{Entity}Controller.php
    │   └── {BC}{Entity}Controller.php (web)
    └── Console/
        └── {CommandName}Command.php
```

### Patrones de código (AGENTS.md — Referencia Rápida)

```
Command:    src/app/{BC}/Application/Command/{Action}{Resource}/{Action}{Resource}Command.php
Handler:    src/app/{BC}/Application/Command/{Action}{Resource}/{Action}{Resource}Handler.php
Query:      src/app/{BC}/Application/Query/{Find|List}{Resource}/{Find|List}{Resource}Query.php
QueryHnd:   src/app/{BC}/Application/Query/{Find|List}{Resource}/{Find|List}{Resource}Handler.php
Response:   src/app/{BC}/Application/Query/Response/{Resource}Response.php
Controller: src/app/{BC}/Presentation/Http/Api{BC}{Entity}Controller.php
Repo intf:  src/app/{BC}/Domain/Repositories/{Resource}RepositoryInterface.php
Repo impl:  src/app/{BC}/Infrastructure/Persistence/Doctrine/Doctrine{Resource}Repository.php
ORM map:    src/app/{BC}/Infrastructure/Persistence/Doctrine/Mapping/{Entity}.orm.xml
DBAL type:  src/app/{BC}/Infrastructure/Doctrine/Types/{Field}Type.php
```

**Shared Kernel:** `src/app/Shared/` con `Application/`, `Domain/` (Entities, ValueObjects, Contracts, Repositories, Service, Event), `Exceptions/`, `Infrastructure/` (Doctrine/Types, Import, Export, Messenger, Event), `Presentation/` (Console, Http, Twig).

---

## 3. Response Envelope API

```json
{"data": ...}           // success
{"data": [], "total": N, "page": P, "pages": M}  // paginated
{"error": "mensaje"}    // error
```

- **Success simple:** `{"data": {...}}` (objeto o array).
- **Paginado:** `{"data": [...], "total": N, "page": P, "pages": M}`.
- **Error:** `{"error": "mensaje"}`.
- Los Responses son DTOs `final readonly class` con factory `fromEntity()` (ver §1).

---

## 4. Excepciones: jerarquía Shared Kernel

```
App\Shared\Exceptions\
├── DomainException (abstract, extends \RuntimeException)      ← violaciones de reglas de negocio
└── ApplicationException (extends \RuntimeException)           ← errores de aplicación
```

**Reglas (AGENTS.md + Apéndice C regla 3):**

- **Domain:** `extends App\Shared\Exceptions\DomainException` — es **abstracta**, no se instancia directamente. Usar la subclase concreta del BC: `throw new InventoryDomainException('...')`.
- **Application:** `extends App\Shared\Exceptions\ApplicationException` — excepciones por caso de uso viven en `{BC}/Application/Exception/` (ej. `PermissionNotFoundException`, `RoleNameAlreadyExistsException`, `UserNotFoundException` en Identity).
- **Nunca usar `\DomainException` de PHP nativo.**
- `DomainException` ofrece `withContext(array)`, `getContext()` y `toArray()` (serialización para logging/respuestas API). `ApplicationException` ofrece `withContext(array)` y `getContext()`.
- Factory methods en las excepciones de aplicación (ej. `RoleNameAlreadyExistsException::withName(...)`, `PermissionNotFoundException::withId(...)`).

---

## 5. Rutas API

```
#[Route('/api/v1/{bc}/{resources}')]
#[IsGranted('resource.action')]
final class Api{BC}{Entity}Controller extends AbstractController
```

- Rutas versionadas bajo `/api/v1/{bc}/{resources}`.
- Autorización por permiso vía `#[IsGranted('resource.action')]` (ej. `inventory.move_stock` es el gate básico de transferencias).
- Controllers API **delgados**: solo validan input, delegan a handlers (Command/Query) y formatean la respuesta.
- Web: controladores separados (`{BC}{Entity}Controller`); formularios públicos usan firewall `api_public` (security: false) — ver Key Decisions en AGENTS.md.
- Permisos: `inventory.move_stock`, etc. — los permisos se definen en Identity BC (seeding verificado, issue #70 resuelto).

---

## 6. Transfer Lifecycle (copia fiel de AGENTS.md)

### Status machine

```
PENDING ──startTransit()──► IN_TRANSIT ──confirmBy()──► COMPLETED
                                │                           │
                                ├──cancelBy()──► CANCELLED   │
                                └──rejectBy()──► REJECTED    │
                                                             │
                                  (old code) ────────────────┘
```

### Ownership rules (en Movement entity)

- `createdByUserId` = quien creó la transferencia (ULID del User)
- `assignedToUserId` = destinatario (resuelto desde `Location.employeeId` → `Employee.userId`)
- `cancelBy(userId)` → solo el creador puede cancelar
- `confirmBy(userId)` → solo el destinatario puede confirmar
- `rejectBy(userId, reason)` → solo el destinatario puede rechazar, guarda `rejectionReason`

### Permission + Authorization

- `inventory.move_stock` es el gate básico
- `TransferAuthorizationService` valida ubicaciones (técnico solo desde su mochila, no hacia su mochila)
- Ownership se valida en los métodos del Domain (`Movement::cancelBy()`, `confirmBy()`, `rejectBy()`)
- `MovementViewScopeResolver` filtra la lista: técnicos solo ven movimientos donde son creador o destinatario

### Stock behavior per type

| Tipo | CreateTransfer | ConfirmTransfer | CancelTransfer / RejectTransfer |
|------|---------------|-----------------|---------------------------------|
| **Serialized** | `reserve()` → status RESERVED | `completeTransfer(dest)` → mueve ubicación + AVAILABLE | `release()` → AVAILABLE |
| **Non-serialized** | Reserve+split: reduce origen, crea batch RESERVED | Delete RESERVED batch, addStock al destino | `release()` RESERVED → AVAILABLE |

### Flow (non-serialized example)

```
Source: 5 AVAILABLE, transferir 3
  → Source: 2 AVAILABLE (reduce)
  → New batch: 3 RESERVED (reserve)

On CONFIRM:
  → Delete RESERVED batch
  → Add 3 to destination (addStock)

On CANCEL/REJECT:
  → Release RESERVED → AVAILABLE
```

**Implementación:** `src/app/Inventory/Application/Command/{CreateTransfer,ConfirmTransfer,CancelTransfer,RejectTransfer}/`

---

## 7. ViewScopeResolver Pattern (Apéndice D de AGENTS.md — copia fiel)

### Problema

Cada usuario debe ver solo los datos que le corresponden según su perfil:

- Técnicos de campo → solo ven stock de **su mochila**
- Clientes → solo ven **su** cliente (no todos)
- Supervisores → solo ven datos de **su zona**
- Admin → ve todo

### Patrón: ViewScopeResolver

Application Service que encapsula la lógica de scoping:

```
┌─────────────────────────────────────────────────────┐
│  Controller                                          │
│    $scope = $resolver->resolve($userId);             │
│    $query->locationId = $scope ?? $request->get(...);│
│    $handler($query);                                 │
└─────────────────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────┐
│  StockViewScopeResolver (Application Service)        │
│    → EmployeeRepository (Org BC) → findByUserId()   │
│    → employee.profile().isTechnician()               │
│    → LocationRepository (Inv BC) → findByEmployee() │
│    → return backpack LocationId (o null)             │
└─────────────────────────────────────────────────────┘
```

### Reglas DDD

- El Resolver vive en `{BC}/Application/Service/` (Application layer)
- Solo lee repositorios de otros BCs (nunca persiste entidades ajenas)
- El controller es delgado: solo obtiene scope y lo pasa al query
- El Domain permanece puro (no sabe de usuarios ni auth)

### Para extender a otros recursos (tickets, clientes)

1. Crear `{Resource}ViewScopeResolver` similar
2. Inyectar repositorios necesarios
3. Usar en el controller/handler correspondiente

### Ejemplos verificados

- `src/app/Inventory/Application/Service/StockViewScopeResolver.php` — resuelve mochila del técnico (Organization → Inventory, solo IDs entre BCs).
- `src/app/Inventory/Application/Service/MovementViewScopeResolver.php` — filtra por creador/destinatario.
- `src/app/Support/Application/Service/SparePartViewScopeResolver.php` — scoping de repuestos.

---

## 8. Otros convenios relevantes (Key Decisions — resumen operativo)

- **Enums persistidos:** PHP 8.4 backed enums (`: string`) DEBEN usar custom DBAL type (`AbstractStringEnumType`). Prohibido `type="string"` en ORM.
- **Soft delete:** columna `deleted_at`, método `softDelete()`, repositorios filtran `deleted_at IS NULL` (ADR-008).
- **Cross-BC:** solo IDs entre BCs; nunca instanciar entidades de otro BC; async (Messenger) para eventos cross-BC, sync solo intra-BC.
- **Controllers delgados:** validan input y delegan; nunca ejecutan SQL/JPQL directo (Apéndice C).
- **Bindings en services.yaml:** toda interface de repositorio necesita binding explícito (`App\X\Domain\Repositories\XInterface: ~`).
- **Modales frontend:** `assets/controllers/modal_controller.js` (Stimulus); cada modal requiere su propio `div[data-controller="modal"]` con trigger + backdrop + content.
