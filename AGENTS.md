# Orbis — Reglas y Convenciones de Código

> **⚠️ SOLO LECTURA / Backup:** La **verdad única de estado** vive en [`CONTEXT.md`](./CONTEXT.md) — status, %, reportes, importers y progreso. Este archivo contiene SOLO reglas de código y convenciones (estable, no cambia por sesión). Historial y estrategia analytics en [`docs/`](./docs/).

Orbis es una aplicación tipo ERP para la gestión de procesos de Automated Cash Management Solutions S.A. Plataforma web + futura app móvil.

Se aplica **Spec Driven Development**: spec primero (`specs/`), implementación después.

## Lectura obligatoria al iniciar sesión (AI-Native bootstrap)

Este repositorio es **AI-Native**: Git es la única fuente de verdad. Ningún agente asume información que no esté documentada. Cualquier IA debe poder incorporarse leyendo SOLO estos archivos:

```
1. cat AGENTS.md                # reglas y convenciones de código (este archivo)
2. cat PROJECT_CONTEXT.md       # contexto general del proyecto
3. cat docs/architecture.md     # arquitectura completa
4. cat docs/coding-standards.md # reglas obligatorias de codificación
5. cat docs/adr/README.md       # índice de decisiones arquitectónicas (ADRs)
6. cat specs/{num}-{bc}/spec.md # spec correspondiente a tu tarea
7. cat CONTEXT.md               # estado REAL del proyecto (única fuente de verdad de estado)
8. cat tasks/IN_PROGRESS.md     # tareas en curso
```

Referencia bajo demanda: `docs/analytics.md` (dashboards/reportes/importers), `docs/history.md` (issues y deuda técnica), `docs/glossary.md` (términos), `docs/dependencies.md` (stack), `docs/conventions.md` (convenciones), `.ai/` (prompts especializados por rol).

**Regla no negociable:** Ningún agente puede tomar una decisión arquitectónica sin crear/actualizar un ADR en `docs/adr/` y actualizar `PROJECT_CONTEXT.md`. Si existe una contradicción entre el código y la documentación, debe reportarla ANTES de continuar.

## Stack

**Backend:** Symfony 8.0 / PHP 8.4, MySQL 8+, PHPUnit 11
**Frontend:** Turbo + TailwindCSS + Stimulus
**Auth:** JWT (API-first for mobile), Symfony Security + #[IsGranted]
**Architecture:** DDD + Hexagonal + CQRS ligero, ULIDs, soft-deletes, XML Doctrine mappings
**IDs:** ULID 26 chars (VARCHAR), `AbstractUlid` en Shared Kernel
**Events:** Domain Events internos (array + pullDomainEvents) + Symfony Messenger async

## Bounded Contexts (13 BCs + Shared Kernel)

**Estado real por BC → `CONTEXT.md` §2** (tabla maestra con % y conteos verificados). No duplicar aquí.

## Workflow Multi-Agente

```
PLANNER ──spec──► QA-REVIEWER ──aprobado──► DEVELOPER ──► TEST-ENGINEER
    ▲                  │                         │
    └──── feedback ◄───┘◄───────── feedback ◄────┘
DEVELOPER ──► FRONTEND ──► SECURITY-REVIEWER ──► QA-REVIEWER FINAL ──► DOCS
```

**Regla:** Una tarea NO avanza sin aprobación de la etapa actual.
**Regla de wiring:** Todo Command, Query, Domain Event y Subscriber debe tener al menos un caller real — verificado por QA-Reviewer antes de aprobar.

### Ciclo por tarea
1. **Planner**: escribe spec en `specs/{num}-{bc}/spec.md` — incluye sección **Wiring** (desde dónde se dispara cada Command/Query)
2. **QA-Reviewer**: aplica checklist DDD al spec → aprueba/rechaza
3. **Developer**: implementa Domain → App → Infra, corre tests, verifica wiring (grep/CodeGraph)
4. **Frontend**: implementa UI (si aplica)
5. **QA-Reviewer**: revisa código final → verifica wiring → commit o feedback

### QA Checklist por capa DDD+CQRS

| Capa | Qué contiene | QA revisa |
|---|---|---|
| **Domain** | Entities, VOs, Aggregates, Domain Events, Repository interfaces, Enums, Exceptions | VOs encapsulan reglas, Entities tienen invariantes, Aggregates con raíz, sin framework leakage |
| **Application** | Commands + Handlers, Queries + Handlers, Response DTOs | Handlers orquestan sin lógica de negocio, Guards cross-BC, transacciones, separación CQS |
| **Infrastructure** | Doctrine repositories, ORM mappings (.orm.xml), Custom types | Repositories implementan interfaces del Domain, mappings correctos, tipos custom sin leakage |
| **Presentation** | HTTP Controllers (Api*, web), Request validation, Response formatting | Controllers delgados (solo delegan), validación de input, permisos REST, formatos consistentes |
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
3. **Seguir las convenciones de este archivo** (Principios, Arquitectura, Apéndice A) para todo código generado
4. **Cumplir `specs/*/tasks.md`** — checklist de implementación
5. Correr `php vendor/bin/phpunit` después de cambios
6. Si un cambio toca múltiples BCs, involucrar al Planner primero
7. DeviceCatalog BC está SUPERSEDED — no desarrollar ahí

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

## Key Decisions (vigentes)

- **Ticket lifecycle:** 11 estados con máquina de estados completa, cancelable desde cualquier estado no-terminal
- **SLA:** por cliente+contrato, no bloquea flujo (campo `slaDeadlineAt` en Ticket)
- **Expense:** rechazo por item individual, evidence obligatoria para tipos específicos; items aprobados se mantienen al reenviar
- **Notification:** fire-and-forget, retención 3 meses, contactos resueltos desde BC origen via tagged resolvers
- **Inventory:** trackingType no cambiable si hay stock, mochila auto-creada con técnico, non-serialized usa PENDING_REVIEW
- **Inventory SUSPECT:** non-serialized sale del cajero → nuevo NonserializedStock con status PENDING_REVIEW
- **Employee.profile:** campo descriptivo, no autorizativo. La autorización va por Role en Identity BC
- **Employee.status:** ACTIVE / ON_VACATION / INACTIVE. ON_VACATION se actualiza automáticamente por ciclo
- **Employee/Contact ↔ User:** relación invertida — Employee.userId y Contact.userId apuntan a User. User NO conoce otros BCs (pureza DDD)
- **STANDBY:** período de trabajo en feriado/fin de semana, NO cambia Employee.status
- **Client:** debe tener al menos 1 contacto activo (excepto en cascada de desactivación)
- **Support:** finding y spare-parts son endpoints separados (UX fluido)
- **Ticket reassign:** solo permitido desde CREATED o ASSIGNED
- **Transformation:** solo productos `isTransformable=true`; da de baja parte A y crea parte B (nuevo stock, nuevo serial)
- **Repair:** mantiene el serial original (los bancos identifican seriales no originales). Sin "reparar con nuevo serial"
- **Origin tracking:** eliminado. SerializedStock es una entidad liviana sin trazabilidad
- **JWT:** Lexik JWT bundle + RSA keys, firewalls `api_jwt` (login/refresh), `api_public` (forgot/reset), `api` (stateless JWT)
- **RefreshToken:** opaque token, hash SHA-256 en BD, rotación en cada refresh, cleanup via `identity:cleanup-refresh-tokens`
- **security_version:** incrementa en deactivate, changePassword y assignRole; EquatableInterface invalida sesión en refresh
- **UC-04 catch:** captura `EmailNotFoundException | DomainException` (no `\Throwable`) — sin email enumeration
- **SLA Monitoring:** DomainEventPublisher → DomainEventBridgeSubscriber → Messenger async → Notification BC
- **WhatsApp:** stub Logger-only para MVP; provider real (Twilio/WATI) en producción
- **PO Suggestions web:** fetch + window.location redirect (evita JSON response en form submit)
- **Public web routes vs API routes:** formularios públicos en controladores web separados + firewall `api_public` (security: false)
- **PHP 8.4 backed enums:** enums `: string` persistidos DEBEN usar custom DBAL type (`AbstractStringEnumType`). Prohibido `type="string"` en ORM
- **LoggerForgotPasswordEmailSender:** forgot-password stubeado (solo log), `MAILER_DSN=null://null`. Provider real pendiente (ver docs/history.md)
- **RecordFailedLogin:** dos flujos — (1) API JWT → handler inyectado en `JwtApiController::login()`; (2) Web form → `LoginFailureSubscriber` escucha `LoginFailureEvent`. Ambos deben implementarse por cada flujo de login nuevo
- **Modales:** `assets/controllers/modal_controller.js` (Stimulus) reemplaza `confirm()` del navegador
- **NonserializedStock:** no se puede `reserve()` un batch que no esté AVAILABLE (invariante en Domain)
- **Modales con Stimulus:** cada modal requiere su propio `div[data-controller="modal"]` con trigger + backdrop + content (targets dentro del mismo controller)

## Relevant Files

- **Specs:** `specs/{num}-{bc}/spec.md` (spec), `plan.md` (plan), `tasks.md` (tasks)
- **Context:** `CONTEXT.md` (verdad única de estado), `PROJECT_CONTEXT.md` (contexto general), `ROADMAP.md` (hoja de ruta), `CHANGELOG_AI.md` (cambios de la transformación)
- **Task board:** `tasks/TODO.md`, `tasks/IN_PROGRESS.md`, `tasks/DONE.md` — mover filas entre tableros al cerrar tareas
- **Docs:** `docs/architecture.md`, `docs/coding-standards.md`, `docs/conventions.md`, `docs/glossary.md`, `docs/dependencies.md`, `docs/folder-structure.md`, `docs/analytics.md`, `docs/history.md`
- **ADRs:** `docs/adr/README.md` (índice), `docs/adr/ADR-001..011`; decisiones no arquitectónicas en `docs/decisions/`
- **Modules:** `docs/modules/{bc}.md` (uno por Bounded Context)
- **AI prompts por rol:** `.ai/{architect,backend,frontend,reviewer,security,qa}.md`
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
- El Resolver vive en `{BC}/Application/Service/` (Application layer)
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
