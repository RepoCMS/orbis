# Orbis — Project Memory

Orbis es una aplicación tipo ERP para la gestión de procesos de Automated Cash Management Solutions S.A. Plataforma web + futura app móvil que incluirá funcionalidad para la localización de personal de campo.

Se aplica **Spec Driven Development**: spec primero (`specs/`), implementación después.

## Stack

**Backend:** Symfony 8.0 / PHP 8.4, MySQL 8+, PHPUnit 11 (447 test files)
**Frontend:** Turbo + TailwindCSS + Stimulus
**Auth:** JWT (API-first for mobile), Symfony Security + #[IsGranted]
**Architecture:** DDD + Hexagonal + CQRS ligero, ULIDs, soft-deletes, XML Doctrine mappings
**IDs:** ULID 26 chars (VARCHAR), `AbstractUlid` en Shared Kernel
**Events:** Domain Events internos (array + pullDomainEvents) + Symfony Messenger async

## Bounded Contexts (13 BCs + Shared Kernel)

| BC | Path | Spec | Tests | Est. % | P0s |
|---|---|---|---|---|---|---|---|---|
| Identity | `src/app/Identity/` | `specs/001-identity-spec/` | 77 files | **~97%** | ✅ 32 commands, 13 queries, tests completos, Scheduler cleanup, rate limiting IP, MFA recovery route + JWT backupCode. ✅ Import Usuarios. ✅ RevokePermissionFromRole, ActivateUser, ChangePermissionDescription wired. ✅ Dashboard KPIs completos |
| Client | `src/app/Client/` | `specs/002-client/` | 80 files | **~96%** | ✅ 34 commands (36 handlers), 16 queries. ✅ Import Clientes + Terminales + Modelos de Terminal + Model Parts. ✅ UI templates completos (13 templates). ✅ 29/29 eventos conectados a Notification BC. ✅ Dashboard KPIs completos. ✅ Reporte Contratos por Vencer |
| Organization | `src/app/Organization/` | `specs/001-organization-spec/` | 60 tests | **~99%** | ✅ 33 commands, 8 queries, todos con tests. ✅ Reportes: Personal Activo, Vacaciones Programadas, Horas Extras. ✅ Dashboard expandido (4 KPIs). ✅ Dual cascade unificado. ✅ Import Employees |
| Support | `src/app/Support/` | `specs/003-support/` | 89 files | **~98%** | ✅ 35 commands, 20 queries. ✅ ATM Module Tree admin CRUD. ✅ MonitorSla + EvaluateScheduledMaintenance scheduler. ✅ Dashboard expandido (avg time by tech). ✅ Reporte Tickets por Cliente. ✅ Scoping en spare parts |
| Inventory | `src/app/Inventory/` | `specs/004-inventory/` | 96 files | **~96%** | ✅ 60 commands, 29 queries. ✅ Import Products + Serialized + NonSerialized + PurchaseOrders. ✅ Reportes: Valorizado, Movimientos, Devolución, Conteos. ✅ Dashboard expandido. ✅ RegisterEmergencyPartUsage wired |
| Expense | `src/app/Expense/` | `specs/007-expense/` | 30 files | **100%** | ✅ Cerrado, dashboard template + KPI gastos aprobados. ✅ 156 tests (289 assertions). ✅ Reportes: Gastos Período, Adelantos Pendientes. ✅ CheckOverdueCashAdvances scheduler. ✅ Import Operating Expenses |
| Location | `src/app/Location/` | `specs/005-location/` | 7 files | **100%** | ✅ Cerrado (spec, read-only, tests mínimos) |
| Notification | `src/app/Notification/` | `specs/006-notification/` | 8 files | **~95%** | ✅ Cross-BC wiring (+40 handlers). ✅ 9 Twig templates. ✅ Canales IN_APP/EMAIL. ✅ 87/87 event types mapeados. ✅ User preferences UI. ✅ SendNotificationCommand wired via NotificationDispatcher |
| Cross-BC | — | `specs/009-cross-bc/` | — | **~98%** | ✅ 21/21 tareas completadas. ✅ Integration tests: EmployeeDeactivationCascade + ClientDeactivationCascade |
| Employee-User Invariant | — | `specs/010a-employee-user-invariant/` | 3 tests | **~98%** | ✅ Invariantes + unlink en deactivate + scheduler + DetectOrphanUsers. ✅ Stimulus modal confirm component |
| SparePart Request Scope | — | `specs/010b-sparepart-request-scope/` | 1 test | **~98%** | ✅ ViewScopeResolver + scoping en commands + list queries |
| Notifications Stock Requests | — | `specs/011-notifications-stock-requests/` | — | **100%** | ✅ Eventos, handler cross-BC, templates seedeados, tests |
| Multi-Item Request | `src/app/Inventory/` | `specs/012-multi-item-request/` | 75 (Request tests) | **100%** | ✅ Implementado completo (ORM, entity, handlers, tests, Stimulus) |
| ATM Module Tree | `src/app/Support/` | `specs/013-atm-module-tree/` | 18 files | **100%** | ✅ Backend + web + Stimulus tree + admin CRUD completo |
| Shared | `src/app/Shared/` | — | 1 test | — | DashboardMetricsHandler test exists |



## Workflow Multi-Agente

```
PLANNER ──spec──► ARCHITECT ──aprobado──► QA-REVIEWER ──aprobado──► DEVELOPER
    ▲                  │                          │                        │
    └──── feedback ◄───┘◄──────── feedback ◄─────┘◄──────── feedback ◄────┘

DEVELOPER ──► TEST-ENGINEER ──► FRONTEND ──► SECURITY-REVIEWER ──► QA-REVIEWER FINAL ──► DEVOPS ──► DOCS
```

**Regla:** Una tarea NO avanza sin aprobación de la etapa actual.  
**Regla de wiring:** Todo Command, Query, Domain Event y Subscriber debe tener al menos un caller real — verificado por el QA-Reviewer antes de aprobar.

### Ciclo por tarea
1. **Planner** explora con CodeGraph/glob/grep, escribe spec en `specs/{num}-{bc}/spec.md` — incluye sección **Wiring** que lista desde dónde se dispara cada Command/Query
2. **QA-Reviewer** aplica checklist DDD al spec → aprueba/rechaza (incluye verificación de wiring en el spec)
3. **Developer** implementa Domain → App → Infra, corre tests
4. **Developer** verifica wiring: grep/CodeGraph de cada Command/Query/Subscriber nuevo para confirmar que tiene caller
5. **Frontend** implementa UI (si aplica)
6. **QA-Reviewer** revisa código final → verifica wiring → commit o feedback

### QA Checklist por capa DDD+CQRS

| Capa | Qué contiene | QA revisa |
|---|---|---|---|
| **Domain** | Entities, VOs, Aggregates, Domain Events, Repository interfaces, Enums, Exceptions | VOs encapsulan reglas, Entities tienen invariantes, Aggregates con raíz, sin framework leakage |
| **Application** | Commands + Handlers, Queries + Handlers, Response DTOs | Handlers orquestan sin lógica de negocio, Guards cross-BC, transacciones, separación CQS |
| **Infrastructure** | Doctrine repositories, ORM mappings (.orm.xml), Custom types | Repositories implementan interfaces del Domain, mappings correctos, tipos custom sin leakage |
| **Presentation** | HTTP Controllers (Api*, web), Request validation, Response formatting | Controllers delgados (solo delegan a handlers), validación de input, permisos REST, formatos consistentes |
| **Cross-BC** | Guards entre BCs, event subscribers, cross-context queries | Solo IDs entre BCs, eventos mapeados, cascada desactivación, guards padre inactivo |
| **Wiring** | Todo Command, Query, Domain Event, Subscriber, CLI command | **Cada comando/query/evento tiene al menos un caller real** — verificado con grep/CodeGraph. Prohibido código huérfano |

## Principios Globales

- Respetar límites de DDD y nunca saltarse capas arquitectónicas
- Nunca introducir framework leakage en Domain
- Nunca modificar bounded contexts no relacionados
- Preferir explicitud sobre magia y composición sobre herencia
- Mantener lógica de negocio dentro de Domain
- Evitar abstracciones especulativas y side-effects ocultos
- Reutilizar contratos existentes del Shared Kernel
- Preservar backward compatibility salvo aprobación explícita
- **Cross-BC: solo IDs entre BCs, nunca instanciar entidades de otro BC**
- **Async para eventos cross-BC** (Messenger), sync solo para intra-BC

## Reglas de Uso para Agentes

1. Usar CodeGraph (`codegraph_explore`, `codegraph_search`) antes de leer archivos aleatorios
2. Guiarse siempre por `specs/{num}-{bc}/` primero, luego código
3. **Seguir las convenciones de `AGENTS.md`** (secciones Principios Globales, Arquitectura, y apéndices) para todo código generado
4. **Cumplir `specs/*/tasks.md`** — checklist de implementación
5. Correr `php vendor/bin/phpunit` después de cambios
6. Usar `use context7` si necesitas revisar nuevas features de las tecnologías usadas.
7. Si un cambio toca múltiples BCs, involucrar al Planner primero
8. DeviceCatalog BC está SUPERSEDED — no desarrollar ahí

## Arquitectura — Referencia Rápida

### Patrones de código

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

### Estructura de ruta API
```
#[Route('/api/v1/{bc}/{resources}')]
#[IsGranted('resource.action')]
final class Api{BC}{Entity}Controller extends AbstractController
```

### Response envelope
```json
{"data": ...}           // success
{"data": [], "total": N, "page": P, "pages": M}  // paginated
{"error": "mensaje"}    // error
```

### Excepciones
- Domain: `extends App\Shared\Exceptions\DomainException`
- Application: `extends App\Shared\Exceptions\ApplicationException`
- Nunca usar `\DomainException` de PHP nativo

## Transfer Lifecycle

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
- `inventory.move_stock` es el gate básico (cualquiera con este permiso puede iniciar)
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

## Key Decisions

- **Ticket lifecycle:** 11 estados con máquina de estados completa, cancelable desde cualquier estado no-terminal
- **SLA:** por cliente+contrato, no bloquea flujo (campo `slaDeadlineAt` en Ticket)
- **Expense:** rechazo por item individual, evidence obligatoria para tipos específicos; items aprobados se mantienen al reenviar
- **Notification:** fire-and-forget, retención 3 meses, contactos resueltos desde BC origen via tagged resolvers
- **Inventory:** trackingType no cambiable si hay stock, mochila auto-creada con técnico, non-serialized usa PENDING_REVIEW
- **Inventory SUSPECT:** non-serialized sale del cajero → nuevo NonserializedStock con status PENDING_REVIEW
- **Employee.profile:** campo descriptivo, no autorizativo. La autorización va por Role en Identity BC
- **Employee.status:** ACTIVE / ON_VACATION / INACTIVE. ON_VACATION se actualiza automáticamente por ciclo
- **Employee/Contact ↔ User:** relación invertida — Employee.userId y Contact.userId apuntan a User. User NO conoce otros BCs (pureza DDD). Migración ejecutada.
- **AfterHourTrip:** eliminado (tabla y código). Ya no es requerido.
- **STANDBY:** período de trabajo en feriado/fin de semana, NO cambia Employee.status
- **Client:** debe tener al menos 1 contacto activo (excepto en cascada de desactivación)
- **Support:** finding y spare-parts son endpoints separados (UX fluido)
- **Ticket reassign:** solo permitido desde CREATED o ASSIGNED; una vez iniciado viaje/espera/atención, NO se puede reasignar
- **Transformation:** solo productos marcados como `isTransformable=true` en el catálogo pueden transformarse. El bodeguero define qué productos son transformables. La transformación da de baja la parte A y crea una nueva parte B (nuevo stock, nuevo serial). Puede cambiar código de producto, serial, o ambos.
- **Repair:** la reparación mantiene el serial original del repuesto. No se permite cambiar serial al reparar (los bancos identifican seriales no originales). Sin opción de "reparar con nuevo serial".
- **Origin tracking:** eliminado. Ya no se rastrea origen de partes (ticket, cliente, cajero, vandalismo). SerializedStock es una entidad liviana sin campos de trazabilidad.
- **JWT:** Lexik JWT bundle + RSA keys configurados, firewall `api_jwt` público para login/refresh, `api_public` para endpoints públicos (forgot-password, reset-password), `api` con stateless JWT para el resto de `/api/`
- **RefreshToken:** opaque token (bin2hex(random_bytes(32))), hash SHA-256 en BD, rotación en cada refresh, cleanup via `identity:cleanup-refresh-tokens` command
- **security_version:** incrementa en deactivate, changePassword y assignRole; SymfonySecurityUser implementa EquatableInterface para invalidar sesión en refresh
- **UC-04 catch:** captura `EmailNotFoundException | DomainException` (no `\Throwable`) para evitar ocultar errores reales sin permitir email enumeration
- **SLA Monitoring:** emite domain events via DomainEventPublisher → DomainEventBridgeSubscriber → Messenger async → Notification BC
- **WhatsApp:** stub Logger-only para MVP; real provider (Twilio/WATI) reservado para producción
- **PO Suggestions web:** fetch + window.location redirect en vez de POST directo a API (evita JSON response en form submit)
- **Public web routes vs API routes:** Los formularios públicos (forgot-password, reset-password) están en controladores web separados (`ForgotPasswordController`). Los POST van a endpoints API via JS fetch. Configurados como PUBLIC_ACCESS en security.yaml y con firewall `api_public` (security: false) para evitar que el entry point JWT intercepte las requests.
- **PHP 8.4 backed enums:** PHP 8.4 elimina la conversión implícita `__toString()` en `backed enums`. Todo enum `: string` persistido via Doctrine DEBE usar un custom DBAL type (`AbstractStringEnumType`). NO se puede mapear como `type="string"` en ORM XML. Los tipos `UserTokenTypeType` y `AuthEventType` se crearon para corregir esto en Identity BC. Revisar otros BCs si aparecen errores similares.
- **LoggerForgotPasswordEmailSender:** El envío de emails de forgot-password está stubeado con `LoggerForgotPasswordEmailSender` (solo log). `MAILER_DSN=null://null`. Para producción, configurar un provider real (Mailtrap/SMTP) y crear un `EmailForgotPasswordEmailSender` real. El token se genera y persiste correctamente pero nunca llega al usuario.
- **RecordFailedLogin:** Se registra en dos flujos distintos: (1) API JWT → `RecordFailedLoginHandler` inyectado directo en `JwtApiController::login()` porque el controller maneja credenciales manualmente sin autenticador Symfony; (2) Web form → `LoginFailureSubscriber` escucha `LoginFailureEvent` que Symfony dispatchea automáticamente. Ambos caminos deben implementarse para cada nuevo flujo de login. El campo `failed_login_attempts` es columna en `users`, no tabla separada.

- **Transfer lifecycle:** IN_TRANSIT → CONFIRM/CANCEL/REJECT. Stock no serializado usa reserve+split (límite de reservas). Ownership: cancelBy = creador, confirmBy/rejectBy = destinatario.
- **Modales:** Se implementó `assets/controllers/modal_controller.js` (Stimulus) para reemplazar `confirm()` del navegador. Cada acción (confirmar, cancelar, rechazar) tiene su propio modal.
- **MovementViewScopeResolver:** Técnicos solo ven movimientos donde son creador (`createdByUserId`) o destinatario (`assignedToUserId`).
- **MovementResponse:** Nuevos campos: `createdByUserId`, `assignedToUserId`, `rejectedAt`, `rejectionReason`.
- **NonserializedStock:** No se puede `reserve()` un batch que no esté AVAILABLE (invariante en Domain).
- **Modales con Stimulus:** Cada modal debe tener su propio `div[data-controller="modal"]` conteniendo tanto el trigger como el backdrop+content (los targets se resuelven dentro del mismo elemento controller).

## Known Issues

- **Client BC UI completo:** UI para contactos, terminales, contratos, warranties y terminal assignments implementada (web controllers + Twig templates). Pendiente: importers y dashboard KPIs.

- **Twig enum comparisons:** Template comparaba `movement.status == 'IN_TRANSIT'` pero el valor real es `in_transit` (lowercase). Corregido en show.html.twig y list.html.twig. Verificar otros templates con comparaciones de enum values.
- **Web routes vs API routes:** Se corrigió la separación de forgot-password y reset-password (se movieron de `ApiIdentityAuthController` a `ForgotPasswordController` web). Queda pendiente revisar Location BC (API-only) si requiere interfaz web.
- **client/show.html.twig:** Se eliminó código huérfano de funcionalidad "sites" (referenciaba `site_show` inexistente).
- **Forgot-password email no enviado:** `LoggerForgotPasswordEmailSender` solo logea el token. `MAILER_DSN=null://null`. Para probar el flujo completo se necesita un provider real (Mailtrap/SMTP) o exponer el token temporalmente vía endpoint dev.
- **RecordFailedLogin no se disparaba:** El handler existía pero ningún controlador ni subscriber lo invocaba. Se corrigió inyectando `RecordFailedLoginHandler` en `JwtApiController` y creando `LoginFailureSubscriber` para el web form login.
- **User::isLocked() no se verificaba:** El modelo de dominio tenía el método `isLocked()` y `recordFailedLogin()` seteaba `lockedUntil` al alcanzar 5 intentos, pero ningún provider ni controller lo chequeaba. Se corrigió agregando la verificación en `SymfonyUserProvider::loadUserByIdentifier()` — cubre ambos flujos (API JWT y web form).
- **RecordFailedLogin:** Se registra en dos flujos distintos: (1) API JWT → `RecordFailedLoginHandler` inyectado directo en `JwtApiController::login()` porque el controller maneja credenciales manualmente sin autenticador Symfony; (2) Web form → `LoginFailureSubscriber` escucha `LoginFailureEvent` que Symfony dispatchea automáticamente. Ambos caminos deben implementarse para cada nuevo flujo de login. El campo `failed_login_attempts` es columna en `users`, no tabla separada.

- **Specs desactualizados vs código real:** 6 spec files tenían % incorrectos (007, 010a, 010b, 011, 004, 012). Corregidos en 2026-07-30.
- **9 orphan commands detectados:** RevokePermissionFromRole, ActivateUser, ChangePermissionDescription, MonitorSla, EvaluateScheduledMaintenance, RegisterEmergencyPartUsage, CheckOverdueCashAdvances, SendNotification, CreateNotificationTemplate. ⚠️ SendNotification sin caller ni tests.
- **MonitorSla + EvaluateScheduledMaintenance + CheckOverdueCashAdvances:** Conectados a scheduler via Messenger messages (2026-07-30).
- **AGENTS.md:** Tabla de BCs actualizada con counts reales de commands/queries/tests (2026-07-30).

## Relevant Files

- **Specs:** `specs/{num}-{bc}/spec.md` (spec), `plan.md` (plan), `tasks.md` (tasks)
- **Conventions:** `AGENTS.md` (incluye reglas no negociables y checklist rápido en apéndices)
- **Routes:** `config/routes/` (uno por BC)
- **Services:** `config/services.yaml`
- **Doctrine:** `config/packages/doctrine.yaml`
- **Messenger:** `config/packages/messenger.yaml`
- **DBAL types:** `src/app/Identity/Infrastructure/Doctrine/Types/{UserTokenTypeType|AuthEventType}.php`
- **Agent configs:** `.opencode/agent/*.md`
- **Modal controller:** `assets/controllers/modal_controller.js`
- **View scope resolvers:** `src/app/Inventory/Application/Service/{StockViewScopeResolver,MovementViewScopeResolver}.php`, `src/app/Support/Application/Service/SparePartViewScopeResolver.php`
- **Transfer lifecycle:** `src/app/Inventory/Application/Command/{CreateTransfer,ConfirmTransfer,CancelTransfer,RejectTransfer}/`

## Apéndice A: Reglas No Negociables

| # | Regla | Consecuencia si se viola |
|---|-------|--------------------------|
| 1 | `declare(strict_types=1)` en TODO archivo PHP | ❌ Rechazo en code review |
| 2 | Enum values en lowercase (snake_case multi-word) | ❌ Rechazo en code review |
| 3 | Constructor `private` + factory method en Entities | ❌ Rechazo en code review |
| 4 | Accessors sin prefijo `get` (`id()`, `name()`, `status()`) | ❌ Rechazo en code review |
| 5 | Domain sin imports de infraestructura (framework leakage) | ❌ Violación arquitectónica |
| 6 | Commands/Queries como `final readonly class` | ❌ Rechazo en code review |
| 7 | Controllers delgados (solo delegan a handlers) | ❌ Violación de Hexagonal |
| 8 | Tests al 80% Domain coverage mínimo | ❌ Gate de CI falla |
| 9 | Sufijo `ValueObject` en VOs | ❌ Rechazo en code review |
| 10 | Repositorios solo con interfaces del Domain | ❌ Violación de Hexagonal |
| 11 | Todo Command/Query debe tener al menos un caller real | ❌ Rechazo en code review |

## Apéndice B: Inconsistencias Vigentes (Deben Corregirse)

| # | Problema | Dónde | Acción requerida |
|---|---|---|---|---|
| 1 | `declare(strict_types=1)` faltante | Algunos VOs y Enums | ✅ Verificado: 0 archivos sin strict_types |
| 2 | DeviceCatalog sin subdirectorios | `DeviceCatalog/Domain/` | ✅ BC eliminado (ver #30) |
| 3 | Enum values en UPPERCASE | `Expense/Domain/Enums/` | ✅ Verificado: enums en lowercase |
| 4 | Factory methods inconsistentes | Mix de `create`, `register`, `hire`, `add` | Unificar en próxima refactorización |
| 5 | Controller naming inconsistente | `LocationController` vs `ApiIdentityUserController` | Unificar: `Api{BC}{Entity}Controller` |
| 6 | Patrón de Domain Events dual | Identity (externo) vs Support/Expense (interno) | Unificar: usar array interno + `pullDomainEvents()` |
| 7 | Doctrine table prefix inconsistente | Identity/Client sin prefijo | Agregar prefijo `identity_`, `client_` (baja prioridad) |
| 8 | Support sin Application/Exception/ | Support BC | ✅ Verificado: directorio existe |
| 9 | ~list_clients~ → client_list | ClientController.php | ✅ Corregido |
| 10 | Código huérfano de "sites" en client/show.html.twig | templates/client/show.html.twig | ✅ Eliminado |
| 11 | Forgot-password y reset-password en API controller | ApiIdentityAuthController | ✅ Movido a ForgotPasswordController web |
| 12 | Enum fields como `type="string"` en ORM (PHP 8.4: requiere custom DBAL type) | UserToken.orm.xml, AuthLog.orm.xml | ✅ Creados UserTokenTypeType + AuthEventType. Revisar otros BCs |
| 13 | RecordFailedLoginHandler sin caller (nunca se disparaba) | Identity BC | ✅ Corregido: JwtApiController + LoginFailureSubscriber |
| 14 | SQL nativo en controller para queries de lectura | EntryController | ✅ Reemplazado por `PurchaseOrderRepositoryInterface::findOpen()` |
| 15 | Permisos inválidos en API controllers (`product.view`, `entry.view`, etc.) | 5 API controllers de Inventory | ✅ Corregido: cambiado a `inventory.view/create/update/delete` |
| 16 | `inventory.manage` no estaba seedeado | SeedPermissionsCommand | ✅ Agregado al seed |
| 17 | Sin stock scoping para técnicos | StockController + API | ✅ Creado `StockViewScopeResolver` en `Inventory/Application/Service/` |
| 18 | Controllers sin `#[IsGranted]` | ClientController.editClient, RoleController.deactivate/activate | ✅ Agregados `client.update`, `role.deactivate`, `role.activate` |
| 19 | TicketActionController sin permisos específicos | 9 métodos en TicketActionController | ✅ Agregado `ticket.update` + seed `ticket.approve` |
| 20 | `resourceLabels` faltante para `service_zone` e `inventory` | role/show.html.twig + form.html.twig | ✅ Agregados; removidos `warehouse`, `repair`, `purchase_order` (inexistentes) |
| 21 | Enum values en UPPERCASE en Twig (`IN_TRANSIT`, `COMPLETED`, etc.) | show.html.twig, list.html.twig | ✅ Corregidos a lowercase `in_transit`, `completed` |
| 22 | Non-serialized stock usaba `reduce()` en CreateTransfer sin crear RESERVED | CreateTransferHandler | ✅ Corregido: reserve+split pattern |
| 23 | Sin scoping de movimientos para técnicos | ListMovementsHandler | ✅ Creado MovementViewScopeResolver |
| 24 | `confirm()` del navegador en acciones de transferencia | show.html.twig | ✅ Reemplazado por modales Stimulus |
| 25 | ListLocationsHandler importado pero no usado en MovementController | MovementController.php | ✅ Eliminado |
| 26 | `spare_part` no existía como recurso de permisos | SeedPermissionsCommand, resourceLabels | ✅ Agregado: `spare_part.view`, `spare_part.request`, `spare_part.update` |
| 27 | `ticket.assistance` no seedeado (13 endpoints lo usan) | SeedPermissionsCommand, ApiSupportTicketController | ✅ Agregado al seed |
| 28 | Spec 012 status "Draft" pero código implementado | `specs/012-multi-item-request/spec.md` | ✅ Actualizado a "Implemented" |
| 29 | Spec 013 no listada en AGENTS.md | `specs/013-atm-module-tree/` | ✅ Agregado a la tabla de BCs |
| 30 | DeviceCatalog BC código presente pese a SUPERSEDED | `src/app/DeviceCatalog/` (24 archivos) | ✅ Eliminado |
| 31 | Numeración duplicada `010-` | `specs/010-employee-user-invariant/` y `specs/010-sparepart-request-scope/` | ✅ Renombrado a `010a-` y `010b-` |
| 32 | Expense y Notification sin ORM mappings | `src/app/Expense/` y `src/app/Notification/` | ✅ Creados 6 archivos .orm.xml |
| 33 | DBAL types no registrados: site_id, site_status, inventory_repair_priority | `config/packages/doctrine.yaml` | ✅ Registrados |
| 34 | DoctrineProductRepository sin findByCode() | `Inventory/Infrastructure/Doctrine/DoctrineProductRepository.php` | ✅ Implementado |
| 35 | TicketStatus sin case REOPENED | `Support/Domain/Enums/TicketStatus.php` | ✅ Agregado |
| 36 | Config lexik_jwt_authentication.yaml faltante | `config/packages/` | ✅ Creado (leía de .env pero sin mapping) |
| 37 | Mapping dirs vacíos para Notification y Shared | `Infrastructure/Persistence/Doctrine/Mapping/` | ✅ Creados |
| 38 | Test count inflado en AGENTS.md (1626 → 585 reales) | `AGENTS.md` | ✅ Corregido |
| 39 | TerminalModel y TerminalWarranty sin ORM mapping | `Client/Infrastructure/Doctrine/Mapping/` | ✅ Creados |
| 40 | UserToken.orm.xml y AuthLog.orm.xml aún con `type="string"` (PHP 8.4) | `Identity/Infrastructure/Persistence/Doctrine/Mapping/` | ✅ Corregido: cambiado a `user_token_type` y `auth_event` |
| 41 | Forgot-password email stubeado | `Identity/Infrastructure/Service/LoggerForgotPasswordEmailSender` | ✅ Creado `EmailForgotPasswordEmailSender` con Symfony Mailer |
| 42 | Support ORM con sintaxis inválida (`<join-column>` en `<one-to-many>`) | `QuickTemplate.orm.xml` | ✅ Corregido |
| 43 | Support `TicketModuleFinding.orm.xml` sin ManyToOne a Ticket | `TicketModuleFinding.orm.xml` | ✅ Agregado |
| 44 | Support enums mapeados como `type="string"` (PHP 8.4) | `TicketWaiting.orm.xml`, `TicketModuleFinding.orm.xml`, `FindingEvidence.orm.xml` | ✅ Creados 3 DBAL types: `WaitingCategoryType`, `FindingTypeType`, `EvidenceMomentType` |
| 45 | Inventory Entity con métodos faltantes | `Movement.php`, `Request.php`, `Location.php`, `SerializedStock.php` | ✅ Agregados: `cancelBy()`, `rejectBy()`, `addItem()`, `fulfillItem()`, `employeeId()`, `isReserved()` |
| 46 | NonserializedStockRepositoryInterface con métodos faltantes | Repository + Implementation | ✅ Agregados: `findByProductAndLocationAndStatus`, `hardDeleteByProductAndLocationAndStatus`, `remove` |
| 47 | TransferAuthorizationService type error | `TransferAuthorizationService.php` | ✅ Corregido: string envuelto en `UserIdValueObject` |
| 48 | Notification BC reportado como ~10% pero código completo | `AGENTS.md` | ✅ Actualizado a ~60%: Domain, Application, ORM, Channels, Repositorios, Controllers completos |
| 49 | Spec 012 Multi-Item Request incompleto (~34%) pese a status "Implemented" | `Request.php`, `Request.orm.xml`, `SubmitRequestCommand/Handler` | ✅ Completado: Domain, ORM, Handlers, Tests, Stimulus |
| 50 | Notification BC sin Twig templates (web controllers fallaban 500) | `templates/notification/` | ✅ Creados 9 templates (inbox, admin, templates CRUD, logs, preferences) |
| 51 | Cross-BC wiring Notification: ~85 eventos no manejados | `CrossBcDomainEventHandler`, `EventType` | ✅ Agregados 16 nuevos handlers + 21 EventType cases + 38 templates |
| 52 | Support BC: 5 query handlers sin test | `GetTicketParent`, `GetAtmModuleTree`, `GetCatCodeComponents`, `SupportDashboardMetrics`, `GetActiveTerminalAssignments` | ✅ Creados 5 test files (49 tests, 169 assertions) |
| 53 | Support BC: `Ticket.parentTicketId` faltaba en Domain Entity (handler lo usaba) | `Ticket.php` | ✅ Agregado property, accessor, factory param |
| 54 | Support BC: `GetCatCodeComponents` usaba `DomainException` abstracta | `GetCatCodeComponentsHandler.php` | ✅ Reemplazado por `TicketNotFoundException::withId()` |
| 55 | Inventory: `SerializedStockStatus` faltaba case `SUSPECT` | `SerializedStockStatus.php` | ✅ Agregado (era referenciado por `markSuscept()` pero no existía) |
| 56 | Inventory: `LocationRepositoryInterface` sin `findByType()` | `LocationRepositoryInterface.php` | ✅ Agregado + implementación Doctrine |
| 57 | Inventory: `EntryItemRepositoryInterface` sin `delete()` | `EntryItemRepositoryInterface.php` | ✅ Agregado + implementación Doctrine |
| 58 | Inventory: `Entry::update()` faltaba en Domain Entity | `Entry.php` | ✅ Agregado |
| 59 | Inventory: `EntryIdValueObject::equals()` faltaba | `EntryIdValueObject.php` | ✅ Agregado |
| 60 | Spec 010a Employee-User Invariant implementado parcial (~50%) | Employee, User entities + invariants | ✅ Invariantes de dominio, LinkEmployeeToUser handler, DetectOrphanUsers mejorado |
| 61 | Spec 010b SparePart Request Scope implementado (~40%) | `SparePartViewScopeResolver` + scoping en handlers | ✅ Scoping para técnicos, vista filtrada |
| 62 | Spec 011 Notifications Stock Requests implementado (~30%) | `SubmitRequestHandler` dispara evento notificable | ✅ RequestSubmitted → Notification BC cross-BC wiring |
| 63 | Spec 013 ATM Module Tree UI implementado (~60%) | Web controller + 4 Twig templates + Stimulus tree | ✅ Backend previo + UI web completada |
| 64 | Inventory query handlers sin cobertura de tests (16 handlers) | 16 handlers en Application/Query/ | ✅ Creados 16 test files (89 tests, 439 assertions) |
| 65 | GeneratePurchaseSuggestionsHandler usa `addMethods()` por `PurchaseOrderItemRepositoryInterface` sin `findLastPurchaseDateByProduct()` | GeneratePurchaseSuggestionsHandlerTest.php + PurchaseOrderItemRepositoryInterface | ✅ Método agregado a interfaz + implementación Doctrine + handler corregido |
| 71 | Refresh token cleanup sin scheduler automático | Identity BC | ✅ `symfony/scheduler` instalado, Schedule.php configurado con cron `0 3 * * *` |
| 66 | Expense BC dashboard web template faltante | templates/expense/dashboard/index.html.twig | ✅ Creado con KPIs: pendientes, adelantos, vencidos, gastos mes, técnicos, tabla |
| 67 | Organization BC dashboard web faltante | OrganizationDashboardController, template | ✅ Creado controller + template + sidebar |
| 68 | Identity BC dashboard template faltante (500 error) | templates/identity/dashboard/index.html.twig | ✅ Creado |
| 69 | RefreshToken.orm.xml faltante (error Doctrine) | Identity/Infrastructure/Persistence/Doctrine/Mapping/ | ✅ Creado |
| 70 | Permisos faltantes: identity.view_dashboard, auth_log.view, organization.view_dashboard, organization.import | SeedPermissionsCommand | ✅ Agregados |
| 72 | InventoryDashboardMetricsHandler warning por `Undefined array key "abc_class"` | `InventoryDashboardMetricsHandler.php:210` | ✅ Agregado `?? 'C'` coalescing |
| 73 | Organization: ListEmployeesQuery sin propiedades de filtro | `ListEmployeesQuery.php` | ✅ Agregados profile, status, search + findByFilters() en repositorio |
| 74 | Organization: tasks.md desactualizado (G-01, G-02 ya resueltos) | `specs/001-organization-spec/tasks.md` | ✅ Actualizado: ORM mappings, import template, query tests |
| 75 | MFA backup codes: sin flujo de uso (recovery route) + sin verificación en API JWT | MfaController, JwtApiController, UserMfaBackupCodeRepositoryInterface | ✅ Añadido /2fa/recovery route + findByCode() repo + backupCode field en JWT login |
| 76 | Inventory BC sidebar: faltaban links a Conteos Físicos, Préstamos | templates/base.html.twig | ✅ Añadidos 2 sidebar links |
| 77 | Spec 013 admin CRUD: controller stubs sin implementación | AtmModuleTreeController (edit, delete, addChild) | ✅ Implementado: ModuleNode entity, repo, CRUD commands/handlers, seed command, permisos, modales |
| 78 | Spec 013 AT-04: árbol editable no integrado como dropdown en hallazgos de tickets | TicketModuleFinding form UI | ✅ Implementado: modal Stimulus con selector de árbol, componente, tipo, notas + evidencia |
| 79 | Importers P2: Import Usuarios, Modelos de Terminal, Órdenes de Compra | Identity/Client/Inventory BC | ✅ Implementados: 3 handlers, 3 parsers, 3 validators, 3 CLI commands, 7 tests |
| 80 | Reportes P1: Gastos por Período, Adelantos Pendientes, Personal Activo | Expense/Organization BC | ✅ 3 generadores, 2 controladores, permisos seedeados |
| 81 | Permisos faltantes: inventory.report, support.report, expense.report, organization.report | SeedPermissionsCommand | ✅ Agregados al seed |
| 82 | Specs desactualizados vs código real (007, 010a, 010b, 011 al 0-10% pero código completo) | `specs/007-expense/spec.md`, `specs/010a-*`, `specs/010b-*`, `specs/011-*` | ✅ Actualizados: status, business rules, commands/queries completos |
| 83 | Spec 004-inventory desactualizado (~50% pero código ~95%) | `specs/004-inventory/spec.md` | ✅ Actualizado: 60 commands, 29 queries, 96 tests, reports, imports |
| 84 | Spec 012-multi-item-request checklist 40/40 sin marcar | `specs/012-multi-item-request/spec.md` | ✅ Marcados todos los items como `[x]` |
| 85 | MonitorSla y EvaluateScheduledMaintenance sin scheduler | Support BC | ✅ Creados MonitorSlaMessage + Handler, EvaluateScheduledMaintenanceMessage + Handler, Schedule.php |
| 86 | CheckOverdueCashAdvances sin scheduler | Expense BC | ✅ Creado CheckOverdueCashAdvancesMessage + Handler, Schedule.php |
| 87 | SendNotificationCommand sin caller real | Notification BC | ✅ `NotificationDispatcher` refactorizado para usar `SendNotificationCommand` |
| 88 | Test de Shared DashboardMetricsHandler no existe | `tests/Unit/Shared/` | ✅ Creado DashboardMetricsHandlerTest |
| 89 | AGENTS.md BC table con counts desactualizados | `AGENTS.md` | ✅ Actualizado: 194 commands, 86 queries, 447 test files |
| 90 | Orphan commands Identity: RevokePermissionFromRole, ActivateUser, ChangePermissionDescription sin caller | Identity BC | ✅ Wired: 5 routes (web + API) |
| 91 | RegisterEmergencyPartUsage sin caller ni test | Inventory BC | ✅ Wired + test (5 tests) |
| 92 | CreateNotificationTemplate sin caller | Notification BC | ✅ Wired (web + API) |
| 93 | Stimulus modal employee-user linking faltante | Spec 010a | ✅ Componente _confirm_modal.html.twig + integración en employee/show |
| 94 | Scoping en list queries de spare parts faltante | Spec 010b | ✅ SparePartViewScopeResolver inyectado en ListTicketPendingSpareParts + ListTicketSpareParts |
| 95 | Integration tests cross-BC faltantes | Spec 009 (G-01, G-03) | ✅ EmployeeDeactivationCascadeTest (12 scenarios) + ClientDeactivationCascadeTest (7 scenarios) |
| 96 | Organization dashboard KPIs faltantes | Organization BC | ✅ 4 KPIs agregados: técnicos disponibles, horas extras, antigüedad, vacaciones |
| 97 | Support dashboard KPIs faltantes | Support BC | ✅ 1 KPI agregado: tiempo promedio por técnico |
| 98 | Inventory dashboard KPIs faltantes | Inventory BC | ✅ 2 KPIs agregados: movimientos por tipo hoy, partes más usadas |
| 99 | Expense dashboard KPIs faltantes | Expense BC | ✅ 1 KPI agregado: total gastos aprobados del mes |
| 100 | Reports P2/P3 no implementados (7 reports) | Multi-BC | ✅ 7 generadores creados: vacaciones, horas extras, tickets por cliente, devolución renuncia, conteos, eventos seguridad, contratos por vencer |
| 101 | Import Model Parts (P2) no implementado | Client BC | ✅ 10 archivos creados + config (Domain, App, Infra, CLI) |
| 102 | Import Operating Expenses (P3) no implementado | Expense BC | ✅ 5 archivos creados + CLI command |

## Apéndice C: DDD Violations to Avoid (Learnings)

| # | Regla | Correcto | Incorrecto |
|---|-------|----------|------------|
| 1 | **Queries de lectura**: usar métodos del repositorio existentes. Si no existe el método, agregarlo a la interface + implementación | `$poRepo->findOpen()` | `$em->getConnection()->fetchAll('SELECT ...')` en controller |
| 2 | **Validación de cantidad**: debe estar en el Handler (Application), no en el controller | `AddEntryItemHandler::__invoke()` con `PurchaseOrderItemRepositoryInterface` | SQL directo en `EntryController::addItem` |
| 3 | **DomainException es abstracta**: no se puede instanciar. Usar subclase concreta del BC | `throw new InventoryDomainException('...')` | `throw new DomainException('...')` |
| 4 | **Custom DBAL types en DQL**: pasar value object + tipo explícito en `setParameter` | `->setParameter('id', $vo, 'purchase_order_item_id')` | `->setParameter('id', (string) $vo)` |
| 5 | **Services.yaml bindings**: toda interface de repositorio debe tener binding explícito | `App\Inventory\Domain\Repositories\XInterface: ~` | Confiar solo en autowiring |
| 6 | **CQS**: Commands por handler con repositorios; Queries de lectura pueden usar métodos del repositorio directamente | Controller llama a `$poRepo->findOpen()` o pasa por Query Handler | Controller ejecuta SQL/JPQL directo |

## Apéndice D: Data Scoping Pattern (ViewScopeResolver)

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

**Reglas DDD:**
- El Resolver vive en `Inventory/Application/Service/` (Application layer)
- Solo lee repositorios de otros BCs (nunca persiste entidades ajenas)
- El controller es delgado: solo obtiene scope y lo pasa al query
- El Domain permanece puro (no sabe de usuarios ni auth)

**Para extender a otros recursos** (tickets, clientes):
1. Crear `{Resource}ViewScopeResolver` similar
2. Inyectar repositorios necesarios
3. Usar en el controller/handler correspondiente

## Apéndice E: Referencia Rápida para Agentes IA

```
¿Vas a crear código? Sigue esto:

1. Archivo PHP → declare(strict_types=1) + namespace + imports + class
2. ValueObject → final readonly class, sufijo *ValueObject, validación en constructor
3. Entity → final class, constructor private, factory method con nombre de dominio
4. Command → final readonly class, props public
5. Handler → final readonly class, __invoke(), DI de interfaces
6. Controller → final class extends AbstractController, #[Route], #[IsGranted]
7. Enum → string backed, values en lowercase snake_case
8. Response → final readonly class, fromEntity() static factory
9. Exception → heredar de Shared Kernel, factory methods
10. Test → final class extends TestCase, test{Scenario}()
```

## Apéndice F: Data & Analytics Strategy

### Overview

Orbis provee tres capacidades analíticas:
1. **Dashboards** — KPIs en tiempo real por BC (web + API)
2. **Reportes** — Exportaciones Excel/PDF programadas o bajo demanda
3. **Importers** — Carga masiva de datos vía CSV/Excel

Principios:
- **Read models separados**: toda lectura analítica usa DBAL directo (nunca carga entidades del Domain)
- **Cada BC dueño de sus dashboards, reportes e importers**
- **Async para importers grandes** (Messenger) o sync batch para <100 filas
- **Librerías**: PhpSpreadsheet (Excel), DomPDF (PDF), Symfony Serializer (CSV)
- **SIEMPRE** usar el patrón `ViewScopeResolver` (Apéndice D) para filtrar datos por perfil

---

### F.1 Dashboard KPIs por BC

#### F.1.1 Nivel Estratégico (Cross-BC — Shared Dashboard)
| KPI | Fuente | Query | Prioridad |
|-----|--------|-------|-----------|
| Tickets abiertos hoy | `tickets` WHERE status NOT IN terminal | COUNT + GROUP BY service_type | P1 |
| Tickets creados hoy vs ayer | `tickets` WHERE DATE(scheduled_at) | COUNT con diff | P1 |
| Técnicos disponibles/ocupados | `employees` + `tickets` | LEFT JOIN con conteo de asignaciones activas | P1 |
| Reparaciones en curso | `repairs` WHERE status IN (pending, in_progress) | COUNT | P1 |
| Alertas de stock mínimo | `stock_minimums` LEFT JOIN `nonserialized_stock` | Filas donde quantity < alert_threshold | P1 |
| SLA breach rate (últimos 30d) | `tickets` con slaDeadlineAt < NOW() AND status != closed | COUNT / total tickets | P2 |
| Movimientos de stock hoy | `stock_movements` WHERE DATE(created_at) | COUNT + diff vs ayer | P2 |

**Ya implementado** en `DashboardMetricsHandler` (DBAL).

#### F.1.2 Nivel Táctico (Per-BC)

**Identity BC** (NUEVO — no existe dashboard)
| KPI | Query | Justificación |
|-----|-------|---------------|
| Usuarios activos / bloqueados / desactivados | `users` GROUP BY status + locked_until | Visibilidad de seguridad |
| Intentos de login fallidos (últimos 7d) | `auth_logs` WHERE event = LOGIN_FAILED AND created_at > 7d | Detección de ataques |
| Tasa de adopción MFA | `user_mfa_configs` WHERE is_enabled = true / total users | Cumplimiento seguridad |
| Usuarios por rol | `users` JOIN `roles` GROUP BY role_id | Distribución de perfiles |
| Eventos de seguridad recientes | `auth_logs` últimas 24h por tipo | Auditoría en vivo |
| Sesiones activas (refresh tokens no expirados) | `refresh_tokens` WHERE expires_at > NOW() AND revoked_at IS NULL | Concurrencia |

**Client BC** (existe parcial — expandir)
| KPI | Query | Estado |
|-----|-------|--------|
| Clientes activos / inactivos | `clients` GROUP BY status | ✅ Existente |
| Terminales por estado | `terminals` GROUP BY status | ✅ Existente |
| Contratos por vencer (30/60/90 días) | `contracts` WHERE end_date BETWEEN NOW() AND NOW()+90d | ❌ No implementado |
| Top clientes por terminales | `terminals` GROUP BY client_id ORDER BY COUNT DESC | ❌ No implementado |
| Contactos activos vs inactivos | `contacts` GROUP BY status | ❌ No implementado |
| Terminales sin asignación | `terminals` LEFT JOIN `terminal_assignments` WHERE assignment IS NULL | ❌ No implementado |
| Crecimiento mensual de clientes | `clients` GROUP BY MONTH(created_at) | ❌ No implementado |

**Organization BC** (API-only — crear web)
| KPI | Query | Estado |
|-----|-------|--------|
| Empleados por perfil | `employees` GROUP BY profile | ✅ API existente |
| Empleados activos / vacaciones / standby | `employees` GROUP BY status | ✅ API existente |
| Distribución por zona de servicio | `employees` JOIN `service_zones` GROUP BY zone | ✅ API existente |
| Horas extras por período | `overtime_records` WHERE status = approved GROUP BY employee | ❌ No implementado |
| Técnicos disponibles ahora | `employees` WHERE profile = technician AND status = active LEFT JOIN tickets activos | P1 |
| Antigüedad promedio por perfil | `employees` GROUP BY profile AVG(DATEDIFF) | ❌ No implementado |
| Vacaciones programadas (próximos 30d) | `vacation_requests` WHERE status = approved AND start_date BETWEEN NOW() AND NOW()+30d | ❌ No implementado |

**Support BC** (existe — expandir con SLA)
| KPI | Query | Estado |
|-----|-------|--------|
| Tickets abiertos por estado | `tickets` GROUP BY status | ✅ Existente |
| Tickets por tipo de servicio | `tickets` GROUP BY service_type | ✅ Existente |
| SLA breach count | `tickets` WHERE slaDeadlineAt < NOW() AND status NOT IN (closed, cancelled) | ❌ No implementado |
| Tiempo promedio de resolución | AVG(TIMEDIFF(closedAt, createdAt)) por service_type | ❌ No implementado |
| Tickets completados hoy/esta semana | `tickets` WHERE closedAt BETWEEN | ❌ No implementado |
| Técnicos con más tickets asignados | `ticket_assignments` GROUP BY employee_id | ❌ No implementado |
| Tiempo promedio por técnico | `tickets` JOIN `ticket_assignments` GROUP BY employee_id | ❌ No implementado |
| Reaperturas (tickets reabiertos) | COUNT WHERE reopened = true / total closed | ❌ Calidad de servicio |
| Tickets por cliente (top 10) | `tickets` GROUP BY client_id | ❌ No implementado |
| Tasa de cancelación | COUNT WHERE status = cancelled / total por período | ❌ No implementado |

**Inventory BC** (API-only — crear web)
| KPI | Query | Estado |
|-----|-------|--------|
| Productos en catálogo | `products` WHERE deleted IS NULL COUNT | ✅ API existente |
| Stock total (serializado) | `serialized_stock` WHERE status = available COUNT | ❌ No implementado |
| Stock total (no serializado) | `nonserialized_stock` WHERE status = available SUM(quantity) | ❌ No implementado |
| Alertas activas | `stock_minimums` LEFT JOIN actual stock | ✅ API existente |
| Pendientes de conciliación | `nonserialized_stock` WHERE status = pending_review COUNT | ✅ API existente |
| Reparaciones en curso | `repairs` WHERE status IN (pending, in_progress) | ✅ API existente |
| Órdenes de compra abiertas | `purchase_orders` WHERE status = open | ✅ API existente |
| Movimientos por tipo (hoy) | `stock_movements` GROUP BY type | ❌ No implementado |
| Stock por ubicación | `serialized_stock` + `nonserialized_stock` GROUP BY location | ❌ No implementado |
| Valor estimado de inventario | SUM(stock × precio) — requiere campo price en Product | ❌ P3 (requiere spec) |
| Partes más usadas (top 10) | `repair_consumables` GROUP BY product_code ORDER BY SUM(quantity) DESC | ❌ No implementado |
| Tiempo promedio de reparación | AVG(TIMEDIFF(completedAt, startedAt)) | ❌ No implementado |

**Expense BC** (stub — implementar)
| KPI | Query | Estado |
|-----|-------|--------|
| Gastos pendientes de aprobación | `operating_expense` WHERE status = submitted COUNT | ✅ Existente |
| Adelantos outstanding | `cash_advance` WHERE status = outstanding COUNT | ✅ Existente |
| Adelantos vencidos (>3 días) | `cash_advance` WHERE status = outstanding AND given_at < NOW()-3d | ✅ Existente |
| Total de gastos del mes | `expense_item` WHERE status = approved AND MONTH = actual SUM(amount) | ❌ No implementado |
| Gastos por tipo | `expense_item` GROUP BY type | ❌ No implementado |
| Total por técnico (mes) | `expense_item` JOIN `operating_expense` GROUP BY employee_id | ❌ No implementado |
| Adelantos por técnico | `cash_advance` GROUP BY employee_id | ❌ No implementado |
| Liquidaciones pendientes | `cash_advance` WHERE status = outstanding AND settlement_direction IS NOT NULL | ❌ No implementado |

---

### F.2 Reportes Excel/PDF

#### F.2.1 Arquitectura

```
┌──────────────────────────────────────────────────────────────┐
│  Application/Report/{Nombre}ReportGenerator.php              │
│    → implements ReportGeneratorInterface                     │
│    → accepts query/filter parameters                         │
│    → uses DBAL Connection read-only                          │
│    → returns array of arrays (raw data)                      │
└──────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────┐
│  Infrastructure/Export/{Excel|Pdf}ExportService.php          │
│    → PhpSpreadsheet (Xlsx) or DomPDF/TCPDF (PDF)             │
│    → recibe datos + config (headers, widths, styles)         │
│    → devuelve Symfony BinaryFileResponse / streamed file     │
└──────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────┐
│  Presentation/Http/{Nombre}ExportController.php              │
│    → #[Route('GET /{bc}/report/{nombre}.{_format}')]        │
│    → #[IsGranted('{bc}.report')]                             │
│    → llama ReportGenerator → ExportService → Response        │
└──────────────────────────────────────────────────────────────┘
```

**Reglas:**
- `ReportGenerator` vive en Application (orquesta datos, NO sabe de formatos)
- `ExportService` vive en Infrastructure (PhpSpreadsheet/DomPDF)
- Controller es delgado: valida permiso, obtiene datos, delega exportación
- **Nunca** instanciar entidades de dominio en reportes — solo DBAL
- Todos los reportes deben exponerse como endpoint descargable y como consola (cron)

#### F.2.2 Reportes Priorizados

| Reporte | BC | Formato | Contenido | Prioridad |
|---------|----|---------|-----------|-----------|
| Inventario valorizado | Inventory | Excel | Producto, código, ubicación, cantidad, valor unitario, valor total | P1 |
| Movimientos de stock | Inventory | Excel/PDF | Fecha, tipo, origen, destino, producto, serial/cantidad, usuario | P1 |
| Reporte de técnicos (Support) | Support | Excel/PDF | Técnico, tickets completados, tiempo promedio, SLA cumplidos | P1 |
| Gastos por período | Expense | Excel | Fecha, técnico, tipo, monto, estado, evidencia | P1 |
| Adelantos pendientes | Expense | Excel | N° adelanto, técnico, monto, fecha, días vencido | P1 |
| Personal activo | Organization | Excel/PDF | Nombre, perfil, zona, fecha ingreso, estado | P1 |
| Vacaciones programadas | Organization | Excel/PDF | Empleado, perfil, fecha inicio, fecha fin, días | P2 |
| Horas extras por período | Organization | Excel | Empleado, fecha, horas, motivo, aprobado por | P2 |
| Devolución por renuncia (valorizado) | Inventory | Excel/PDF | Producto, serial, estado (OK/faltante/dañado), valor estimado | P2 |
| Tickets por cliente | Support | Excel | Cliente, N° ticket, tipo, fecha, estado, técnico, SLA | P2 |
| Historial de eventos de seguridad | Identity | Excel/PDF | Fecha, usuario, evento, IP, user-agent | P2 |
| Contratos por vencer | Client | Excel/PDF | Cliente, tipo contrato, inicio, fin, días restantes, contacto | P3 |
| Terminales por cliente | Client | Excel | Cliente, serial, tipo, modelo, estado, última asignación | P3 |
| Conteos de inventario | Inventory | Excel | Producto, ubicación, cantidad sistema, cantidad contada, diferencia | P3 |

#### F.2.3 Dependencias Composer

```json
"require": {
    "phpoffice/phpspreadsheet": "^3.0",  // Excel (Xlsx, Csv)
    "dompdf/dompdf": "^3.0"              // PDF (alternativa: tecnickcom/tcpdf)
}
```

#### F.2.4 Reportes Programados (Cron)

- Reportes que se generan automáticamente → `Console/Command/{GenerateNombreReportCommand}`
- Se almacenan en `var/reports/{bc}/{nombre}_{Y-m-d}.{xlsx|pdf}`
- Se envían por email usando `Notification BC` (cuando esté lista)
- Cleanup: retention 90 días via `console command`

---

### F.3 Importers (Carga Masiva)

#### F.3.1 Arquitectura

```
Sync (< 100 filas):
┌──────────┐    ┌────────────────────────────┐    ┌───────────────┐
│ Controller│───►│ Import{Recurso}Handler      │───►│ Entity       │
│ (file     │    │ → valida cabeceras          │    │ Repository   │
│  upload)  │    │ → parsea filas              │    │ (persist)    │
└──────────┘    │ → Domain Events por fila     │    └───────────────┘
                │ → errores → ImportResult      │
                └──────────────────────────────┘

Async (≥ 100 filas):
┌──────────┐    ┌──────────────┐    ┌────────────────┐    ┌───────────────┐
│ Controller│───►│ Import{BC}   │───►│ Handler        │───►│ Entity       │
│ (file     │    │ Message      │    │ (Messenger)    │    │ Repository   │
│  upload)  │    │ (file path)  │    │ → batch de 50  │    │ (persist)    │
└──────────┘    └──────────────┘    │ → Domain Events │    └───────────────┘
                                    │ → errores log   │
                                    │ → notificación  │
                                    └────────────────┘
```

#### F.3.2 Patrón para cada Importer

```
src/app/{BC}/
  Application/
    Command/Import{Recurso}/
      Import{Recurso}Command.php          (final readonly class)
      Import{Recurso}Handler.php          (final readonly class)
      Import{Recurso}Message.php          (async, implements AsyncMessageInterface)
      Import{Recurso}MessageHandler.php   (Messenger handler)
      ImportResult.php                    (response: imported, errors, skipped)
  Infrastructure/
    Import/
      {Recurso}FileParser.php             (CSV/Excel → array)
      {Recurso}RowValidator.php           (fila individual → errors[])
    Console/
      Import{Recurso}Command.php          (CLI: php bin/console {bc}:import:{recurso} file.csv)
  Presentation/
    Http/
      Api{BC}ImportController.php         (POST /api/v1/{bc}/import/{recurso})
```

#### F.3.3 Importers Priorizados

| Importer | BC | Formato | Datos por fila | Prioridad |
|----------|----|---------|----------------|-----------|
| Clientes | Client | CSV/Excel | RUC, nombre, dirección, email contacto, teléfono | P1 |
| Terminales | Client | CSV/Excel | Serial, cliente (RUC), tipo, modelo, ubicación | P1 |
| Productos | Inventory | CSV/Excel | Código, nombre, tracking type, descripción | P1 ✅ |
| Stock serializado | Inventory | CSV/Excel | Producto (código), serial, ubicación (ULID) | P1 ✅ |
| Stock no serializado | Inventory | CSV/Excel | Producto (código), ubicación (ULID), cantidad | P1 ✅ |
| Empleados | Organization | CSV/Excel | Identificación, nombre, email, perfil, zona | P1 |
| Usuarios | Identity | CSV/Excel | Username, email, rol (nombre), empleado vinculado | P2 |
| Modelos de terminal | Client | CSV/Excel | Fabricante, modelo, descripción | P2 |
| Partes de modelo | Client | CSV/Excel | Modelo, nombre, partNumber, nivel, padre | P2 |
| Órdenes de compra | Inventory | CSV/Excel | Referencia, proveedor, items (código + cantidad) | P2 |
| Gastos operativos | Expense | CSV/Excel | Empleado, tipo, monto, fecha, evidencia (path) | P3 |
| Movimientos de stock | Inventory | CSV/Excel | Tipo, producto, serial/cantidad, origen, destino | P3 |

#### F.3.4 Reglas de Implementación

1. **Parser validación**: Verificar cabeceras CSV coinciden con plantilla esperada
2. **RowValidator**: Validar cada fila independientemente (errores no bloquean el batch)
3. **Transaccional**: Cada fila es una transacción individual (no falla todo el lote)
4. **Result**: Devolver `ImportResult{int imported, int errors, int skipped, array errorDetail}`
5. **Domain Events**: Publicar evento por cada fila importada (ej. `ClientImported`, `TerminalImported`)
6. **Dry run**: Modo preview que parsea y valida sin persistir
7. **Plantilla descargable**: Endpoint GET `/api/v1/{bc}/import/{recurso}/template` que devuelve CSV de ejemplo
8. **Para async** (≥100 filas): Subir archivo → almacenar en `var/imports/` → dispatch Message → response 202 con job ID → endpoint de status

#### F.3.5 Validaciones Cross-BC

| Importer | Validación cross-BC |
|----------|--------------------|
| Clientes | `cityId` existe en Location BC |
| Terminales | `clientId` existe y está activo en Client BC |
| Empleados | `userId` existe en Identity BC (si se vincula), `serviceZoneId` existe en Organization BC |
| Usuarios | `roleId` existe, username único, email único |
| Productos | `productCodeBySupplier` único en Inventory BC |
| Stock | `productId` existe en Inventory BC, `locationId` existe |

---

### F.4 Estándares de Implementación

#### F.4.1 Dashboard
- Handler SIEMPRE usa DBAL Connection directo (nunca repositorios del Domain)
- Response DTO: `final readonly class` con propiedades tipadas
- Controller web: Twig template con grid de KPIs (cards) + tablas + barras
- Controller API: `{"data": {...}}` envelope
- Sufijo: `{BC}DashboardController` (web), `Api{BC}DashboardController` (API)
- Template: `templates/{bc}/dashboard/index.html.twig`
- Tests: Handler test con datos reales (insert + assert query results)
- **Performance**: Dashboard query debe ejecutarse en <500ms. Usar índices compuestos en tablas grandes. Cachear queries de dashboards con `#[Cache]` si es necesario.

#### F.4.2 Reporte
- Generador: `{Nombre}ReportGenerator` en Application layer
- Export: `{Excel|Pdf}ExportService` en Infrastructure (Servicio genérico reutilizable)
- Controller: `{Nombre}ExportController` con `#[Route]` y `#[IsGranted]`
- Formato por extensión en URL: `/report/{nombre}.xlsx` o `/report/{nombre}.pdf`
- Stream response (no cargar archivo completo en memoria para grandes datasets)
- Tests: Generator test con DBAL, Export test con archivos temporales

#### F.4.3 Importer
- Parser: `{Recurso}FileParser` — clase aparte, inyectable, testeable
- Validator: `{Recurso}RowValidator` — valida una fila, devuelve errores
- Handler: `Import{Recurso}Handler` — orquesta parse → validate → persist
- Response: `ImportResult` con conteos y detalle de errores
- Tests: Handler test con CSV/Excel de prueba (archivos fixtures en `tests/Fixtures/Import/`)
- **No usar** `LOAD DATA INFILE` de MySQL — siempre pasar por Domain para eventos y validaciones
- Límite sync: 100 filas. Sobre eso → async Message
- Archivos subidos se guardan en `var/imports/{bc}/{recurso}/{Y-m-d}/` y se limpian a los 7 días

#### F.4.4 Seguridad
| Aspecto | Regla |
|---------|-------|
| Dashboard | `#[IsGranted('{bc}.view')]` |
| Reporte | `#[IsGranted('{bc}.report')]` — seedear nuevo permiso `{bc}.report` |
| Import | `#[IsGranted('{bc}.import')]` — seedear nuevo permiso `{bc}.import` |
| Template descargable | Público (no requiere autenticación) |
| Archivos subidos | Validar extensión (.csv, .xlsx), tamaño máx 10MB, scan antivirus (si disponible) |
| Scoping | Reportes e importers deben respetar `ViewScopeResolver` (Apéndice D) |

---

### F.5 Resumen de Dependencias a Instalar

```json
"require": {
    "phpoffice/phpspreadsheet": "^3.0",
    "dompdf/dompdf": "^3.0"
}
```

No hay otras dependencias externas. CSV usa `Symfony Serializer\Encoder\CsvEncoder` (ya disponible). Excel y PDF son las únicas librerías nuevas.

---

### F.6 Prioridad de Ejecución

| Fase | Qué | Por qué |
|------|-----|---------|
| 🥇 Fase 1 | Completar dashboards existentes + Identity BC dashboard + Expense BC dashboard | Bajo esfuerzo, alto impacto visual |
| 🥇 Fase 1 | Reportes P1 (Inventario valorizado, Movimientos, Técnicos, Gastos, Personal) | Ya especificados en specs |
| 🥇 Fase 1 | Importers P1 (Clientes, Terminales, Productos, Stock, Empleados) | Carga masiva es necesaria para onboarding |
| 🥈 Fase 2 | Reportes P2 (Vacaciones, Horas extras, Devolución renuncia, Auditoría) | Dependen de Features existentes |
| 🥈 Fase 2 | Importers P2 (Usuarios, Modelos, Partes, Órdenes de compra) | Menor urgencia |
| 🥉 Fase 3 | Reportes P3 | Baja prioridad |
| 🥉 Fase 3 | Importers P3 | Baja prioridad |
```

