# Arquitectura de Orbis

> **Documento:** Arquitectura general del sistema (AI-Native Repository)
> **Fecha:** 2026-08-06
> **Fuente primaria de estado:** `CONTEXT.md` (única fuente de verdad de estado)
> **Reglas de código:** `AGENTS.md`

---

## 1. Vista general

**Orbis** es una aplicación tipo ERP para la gestión de procesos de *Automated Cash Management Solutions S.A.* (plataforma web + futura app móvil, API-first). Se aplica **Spec Driven Development** (spec primero en `specs/{num}-{bc}/`, implementación después).

| Aspecto | Tecnología (verificada) |
|---------|------------------------|
| **Lenguaje** | PHP >= 8.4 (`composer.json`) |
| **Framework** | Symfony 8.0 (`"symfony/framework-bundle": "8.0.*"`) |
| **ORM** | Doctrine ORM `^3.6.7` (documentación previa decía "ORM 2.x" — desactualizado) |
| **Base de datos** | MySQL 8.0 (`DATABASE_URL="mysql://root:change-me@127.0.0.1:3306/cmscomec_orbis?serverVersion=8.0&charset=utf8mb4"` en `.env.example`) |
| **Arquitectura** | DDD + Hexagonal (Ports & Adapters) + CQRS ligero |
| **IDs** | ULID de 26 chars (`AbstractUlid` en Shared Kernel, `symfony/uid`) |
| **Mensajería** | Symfony Messenger (transporte Doctrine) — async para cross-BC |
| **Auth** | JWT (Lexik) API-first + Symfony Security + MFA/TOTP (`spomky-labs/otphp`) |
| **Frontend** | Turbo (`symfony/ux-turbo`) + Stimulus (importmap) + Tailwind (`symfonycasts/tailwind-bundle`) + Twig |
| **Tests** | PHPUnit 11 |
| **Scheduler** | `symfony/scheduler` (8.0.*) + `src/Schedule.php` |

### Capas por BC (4 capas + Console)

```
src/app/{BC}/
├── Domain/           # Entities, VOs, Enums, Repository interfaces, Domain Events, Exceptions
├── Application/      # Commands + Handlers, Queries + Handlers, Response DTOs, Services, Reports
├── Infrastructure/   # Doctrine repositories + XML mappings, DBAL types, integrations
└── Presentation/     # HTTP Controllers (Api*/web), Console commands
```

Regla de `services.yaml`: el autowiring por BC excluye `Domain/` (clases puras sin dependencias de framework).

---

## 2. Diagrama de capas por BC

```
┌─────────────────────────────────────────────────────────────────┐
│                       PRESENTATION (HTTP)                        │
│  Api{BC}{Entity}Controller   →  {BC}{Entity}Controller (web)     │
│  #[Route('/api/v1/{bc}/{resources}')]  +  #[IsGranted(...)]      │
└───────────────────────────────┬─────────────────────────────────┘
                                │ delega (controllers delgados)
┌───────────────────────────────▼─────────────────────────────────┐
│                       APPLICATION (CQRS)                         │
│  Command/{Action}{Resource}/{Action}{Resource}Command            │
│  Command/{Action}{Resource}/{Action}{Resource}Handler            │
│  Query/{Find|List}{Resource}/{Find|List}{Resource}Query          │
│  Query/{Find|List}{Resource}/{Find|List}{Resource}Handler        │
│  Query/Response/{Resource}Response.php (DTO)                     │
│  Service/{Resource}ViewScopeResolver.php  (scoping por perfil)   │
│  Report/{Nombre}ReportGenerator.php                              │
└───────────────┬──────────────────────────────┬──────────────────┘
                │                             │
┌───────────────▼──────────────────────────────▼──────────────────┐
│                       DOMAIN (puro, sin framework)               │
│  Entities/{Entity}.php        ValueObjects/{Name}ValueObject.php │
│  Enums/{Name}Enum.php (string-backed, lowercase snake_case)      │
│  Repositories/{Resource}RepositoryInterface.php (puertos)        │
│  Events/{EventName}.php       Exceptions/{BC}DomainException.php │
└───────────────┬─────────────────────────────────────────────────┘
                │ implementa (adaptadores)
┌───────────────▼─────────────────────────────────────────────────┐
│                      INFRASTRUCTURE                              │
│  Persistence/Doctrine/Doctrine{Resource}Repository.php           │
│  Persistence/Doctrine/Mapping/{Entity}.orm.xml (mappings XML)    │
│  Doctrine/Types/{Field}Type.php (152 tipos DBAL registrados)     │
│  Service/{IntegrationService}.php  Console/{Command}Command.php  │
└───────────────────────────────┬─────────────────────────────────┘
                                ▼
                     MySQL 8.0 (tablas `orbis_*`, soft-deletes)
```

---

## 3. Mapa de Bounded Contexts

**8 BCs de negocio activos + Shared Kernel.** El directorio `src/app/` contiene: `Client`, `Expense`, `Identity`, `Inventory`, `Location`, `Notification`, `Organization`, `Shared`, `Support`.

> ⚠️ **DeviceCatalog**: listado como SUPERSEDED en AGENTS.md y con autoload PSR-4 en `composer.json` (`"App\\DeviceCatalog\\": "src/app/DeviceCatalog/"`), pero **el directorio `src/app/DeviceCatalog/` NO existe** en disco. No desarrollar ahí.

| BC | Estado real (CONTEXT.md) | Responsabilidades (AGENTS.md / docs/modules) |
|----|--------------------------|----------------------------------------------|
| **Identity** | ~85% | Autenticación (JWT API + web form), autorización RBAC (Roles + Permisos), MFA/TOTP, refresh tokens (hash SHA-256 + rotación), lockout por intentos fallidos, auditoría de eventos de seguridad, `security_version` + EquatableInterface |
| **Client** | ~70% | Clientes, contactos (mínimo 1 activo), terminales ATM (asignaciones, garantías, modelos), contratos, sitios, SLA por cliente+contrato |
| **Organization** | ~67% | Empleados (perfil descriptivo no autorizativo; `Employee.userId` apunta a User), zonas de servicio, vacaciones, sobretiempo, períodos especiales (STANDBY), auto-actualización de `Employee.status` por ciclo |
| **Support** | ~85% | Tickets con máquina de 11 estados, jornadas, findings + spare-parts (endpoints separados), reassign solo desde CREATED/ASSIGNED, ATM Module Tree (013) |
| **Inventory** | ~95% | Productos (serializado / no serializado), stock, órdenes de compra, entradas, transferencias (Transfer Lifecycle), reparaciones, transformaciones, solicitudes, conteos físicos, mínimos de stock, mochilas auto-creadas |
| **Expense** | 100% | Gastos operativos con rechazo por item, evidencia obligatoria por tipo, anticipos de caja, liquidación |
| **Location** | ~95% (API-only) | Geografía: regiones, provincias, ciudades (solo queries de lectura + seed). 37 archivos PHP, 1 solo controller |
| **Notification** | ~60% | Notificaciones fire-and-forget, retención 3 meses, contactos resueltos desde BC origen vía tagged resolvers, WhatsApp stub (Logger-only) |
| **Shared Kernel** | Completo | Contratos cross-BC: `AbstractUlid`, `DomainEvent`, `DomainEventPublisherInterface`, `TransactionManagerInterface`, `IdGeneratorInterface`, tipos DBAL abstractos, importación (CSV), exportación (PDF/Excel), idempotencia |
| **DeviceCatalog** | SUPERSEDED | ⚠️ Sin directorio en disco; solo queda el autoload en composer.json |

### Conteos reales por BC (CONTEXT.md §2)

| BC | Tests | Commands | Queries | Controllers | Reportes | Importers |
|----|-------|----------|---------|-------------|----------|-----------|
| Identity | 77 | 32 | 13 | 15 | 1 | 1 |
| Client | 79 | 37 | 16 | 18 | 2 | 3 |
| Organization | 61 | 33 | 8 | 8 | 3 | 1 |
| Support | 93 | 35 | 20 | 12 | 2 | 0 |
| Inventory | 96 | 60 | 29 | 28 | 4 | 3 |
| Expense | 30 | 15 | 8 | 5 | 2 | 1 |
| Location | 7 | — | — | 1 | — | — |
| Notification | 8 | 8 | 5 | 8 | — | — |

---

## 4. Flujo de una petición

### 4.1 Request de escritura (Command)

```
HTTP POST /api/v1/{bc}/{resources}
  │
  ▼
Api{BC}{Entity}Controller (delgado: valida input, delega)
  │  #[IsGranted('resource.action')]
  ▼
{X}Command (final readonly class, props public)
  │  dispatcher (symfony/messenger sync)
  ▼
{X}Handler (__invoke): orquesta repositorios del Domain
  │  - guards cross-BC (IDs de otros BCs vía resolvers)
  │  - transacciones (TransactionManagerInterface, si aplica)
  │  - idempotencia (IdempotencyService, si aplica)
  ▼
{Resource}RepositoryInterface (puerto)
  ▼
Doctrine{Resource}Repository (adaptador, Infrastructure)
  ▼
Entity del Domain (factory + invariantes, VOs encapsulan reglas)
  └─ domain events → pullDomainEvents() → SymfonyDomainEventPublisher
     → DomainEventBridgeSubscriber → Messenger async_events (cross-BC)
```

### 4.2 Request de lectura (Query)

```
HTTP GET /api/v1/{bc}/{resources}
  │
  ▼
Controller → obtiene scope con {Resource}ViewScopeResolver (si aplica)
  │  ejemplo: StockViewScopeResolver → técnicos solo ven su mochila
  ▼
{Find|List}{Resource}Query → Handler
  ▼
Repository (métodos de lectura) o Query handler
  ▼
{Resource}Response (DTO, fromEntity())
  │
  ▼
Response envelope: {"data": ...} | {"data":[],"total":N,"page":P,"pages":M} | {"error":"..."}
```

**Regla CQS (Apéndice C de AGENTS.md):** las queries de lectura usan métodos del repositorio existentes (o se agregan a la interface) — prohibido SQL/JPQL directo en controllers.

### 4.3 Flujo de eventos (patrón ADR-012: Cross-BC Event-Driven Cascade)

```
Domain Entity (BC A) → domainEvent (se acumula en pullDomainEvents)
  → SymfonyDomainEventPublisher (App\Shared\Infrastructure\Event)
  → DomainEventBridgeSubscriber (App\Shared\Infrastructure\Messenger)
  → DomainEventMessage (App\Shared\Infrastructure\Messenger\Message)
  → transporte async_events (Doctrine transport, queue_name=events, retry 3)
  → Handler en BC B (subscriber/handler suscrito al evento)
```

- **Sync solo intra-BC**; **async para cross-BC** (regla AGENTS.md).
- Ejemplo real: SLA Monitoring (Identity/Support → Notification BC).
- Ejemplo de cascade: desactivación de Employee → cascada de desactivación en otros BCs (ADR-012), auto-creación de mochila (012-auto-create-backpack, `AutoCreateBackpackHandler`).

---

## 5. Persistencia

| Mecanismo | Detalle verificado |
|-----------|--------------------|
| **Mappings** | Doctrine XML: `src/app/{BC}/Infrastructure/Persistence/Doctrine/Mapping/{Entity}.orm.xml` — 9 directorios Mapping configurados en `doctrine.yaml` (Identity, Client, Organization, Inventory, Location, Support, Expense, Notification, Shared). ⚠️ El de **Shared está vacío** (0 .orm.xml en disco). Conteo real: Client 8, Identity 8, Inventory 15, Support 19, Organization 7, Expense 3, Location 3, Notification 3 = 66 mappings |
| **DBAL custom types** | **152 tipos registrados** en `config/packages/doctrine.yaml` (conteo verificado). Patrón: `AbstractStringValueObjectType` (IDs/VOs) y `AbstractStringEnumType` (enums string-backed — prohibido `type="string"` en ORM) |
| **IDs** | ULID VARCHAR(26) — `AbstractUlid` (Shared/Domain), `AbstractUlidType` (Shared/Infrastructure/Doctrine/Types), generados por `UlidGenerator` (`IdGeneratorInterface`) |
| **Soft-deletes** | ADR-008. `TimestampableInterface` + `TimestampsSubscriber` (auditoría de fechas). Soft-delete por comando (`SoftDeleteUser`, `SoftDeleteEmployee`) |
| **Naming** | `doctrine.orm.naming_strategy.underscore_number_aware` (ADR-013: prefijos de tabla `orbis_*` ⚠️ verificar en migraciones) |
| **Migraciones** | 31 migraciones en `migrations/` (Version2026…; formato `VersionYYYYMMDDHHMMSS.php`) |
| **schema_filter** | Excluye `role_permissions` y `messenger_messages` |

### Messenger (config/packages/messenger.yaml — verificado)

| Transport | Uso | Retry |
|-----------|-----|-------|
| `async_events` | Cross-BC domain events (`DomainEventMessage`) + importaciones (`ImportClientsMessage`, `ImportTerminalsMessage`) | 3 reintentos, delay 5s, multiplier 2 |
| `async_notifications` | Envío de notificaciones (`SendNotificationMessage`) | 5 reintentos, delay 10s, multiplier 3 |
| `failed` | Failure transport (`doctrine://default?queue_name=failed`) | — |
| `sync` | Intra-BC (Commands/Queries) | — |

DSN: `MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0` (.env.example).

---

## 6. Frontend

| Componente | Detalle verificado |
|------------|--------------------|
| **Templates** | Twig (`symfony/twig-bundle`, `twig/extra-bundle`). 124 archivos en `templates/` organizados por BC (base, components, dashboard, email, security, user, role, client, employee, inventory, support, service_zone, site, expense, notification, organization, verification) |
| **Turbo** | `symfony/ux-turbo` ^3.2 — `@hotwired/turbo` registrado en `importmap.php` + `assets/controllers.json` (`turbo-core` eager, autoimport de Mercure stream source) |
| **Stimulus** | `@hotwired/stimulus` + `@symfony/stimulus-bundle` en `importmap.php`. Controllers en `assets/controllers/`: `csrf_protection`, `dashboard`, `hello`, `modal` (reemplaza `confirm()`), `module_finding`, `multi_item_form`, `notification_badge`, `prevent_double_submit`, `table_filter`, `tree` |
| **Estilos** | Tailwind CSS vía `symfonycasts/tailwind-bundle` ^0.12 (`symfonycasts_tailwind.yaml`) + `assets/styles/app.css` |
| **Assets** | Asset Mapper (`asset_mapper.yaml`) — sin build de Webpack/Vite |
| **JS runtime** | Navegador moderno: importmap nativo; sin bundler. `package.json` solo tiene `better-sqlite3` (herramienta dev) |

> ⚠️ **Alpine.js**: documentación previa mencionaba "Stimulus, Alpine.js", pero `importmap.php` **no registra Alpine.js** — no hay evidencia de su uso. TODO: requiere validación.

---

## 7. ADRs relevantes

Fuente: `docs/adr/README.md` + archivos en `docs/adr/`.

| ADR | Título | Estado |
|-----|--------|--------|
| ADR-001 | Domain-Driven Design with Hexagonal Architecture | Accepted (2026-08-06) |
| ADR-002 | CQRS Pattern for Commands and Queries | Accepted |
| ADR-003 | ULID as Primary Identifier | Accepted |
| ADR-004 | Custom DBAL Types for Domain Primitives | Accepted |
| ADR-005 | Doctrine ORM for Persistence | Accepted |
| ADR-006 | Symfony Messenger for Event Bus | Accepted |
| ADR-007 | Shared Kernel for Cross-BC Integration | Accepted |
| ADR-008 | Soft Delete Pattern | Accepted |
| ADR-011 (archivo `011-transaction-manager-interface.md`) | TransactionManagerInterface | Accepted |
| ADR-012 (archivo `012-cross-bc-event-cascade.md`) | Cross-BC Event-Driven Cascade | Accepted |
| ADR-013 (archivo `013-table-prefixes.md`) | Table Prefixes | Accepted |

> ⚠️ Nota de numeración: el README indexa ADR-001..008. Los siguientes existen como `011-`, `012-`, `013-` (números 009 y 010 no tienen archivo propio; el 009 corresponde a la spec `009-cross-bc` y el 010 a las specs `010a/010b`).

---

## 8. Discrepancias detectadas (⚠️)

| # | Discrepancia | Detalle |
|---|--------------|---------|
| 1 | **Doctrine ORM versión** | `composer.json` declara `doctrine/orm: ^3.6.7` (ORM 3.x); documentación previa (PROJECT_CONTEXT.md, eliminado 2026-10-22) decía "Doctrine ORM 2.x" |
| 2 | **Alpine.js** | Mencionado en documentación previa pero no registrado en `importmap.php` |
| 3 | **compose.yaml** | Usa imagen **PostgreSQL 16** (scaffold Symfony por defecto), pero la base real es **MySQL 8.0** según `.env.example` (db `cmscomec_orbis`). El override solo expone puertos y añade Mailpit |
| 4 | **DeviceCatalog** | Autoload PSR-4 declarado en composer.json pero el directorio `src/app/DeviceCatalog/` no existe |
| 5 | **Mapping XML de Shared** | Configurado en `doctrine.yaml` pero el directorio está vacío (0 archivos) |
| 6 | **Contador de archivos** | CONTEXT.md reporta 1411 archivos PHP (CodeGraph); conteo en disco con `Get-ChildItem`: **1863** (incluye código legacy en `src/`) |

---

## 9. Setup rápido

```bash
composer install
npm install
php bin/console doctrine:migrations:migrate
php bin/console app:permissions:seed
php bin/console app:roles:seed
php bin/console app:seed:admin
symfony server:start
```

## 10. Limitaciones conocidas

| Issue | Impacto | Resolución |
|---|---|---|
| Rate limiting en login | Sin límite de intentos a nivel aplicación | Pendiente |
| Expiración de password | No implementada | Pendiente |
| Cleanup de refresh tokens | Manual vía `identity:cleanup-refresh-tokens` | Programar cron |
| Forgot-password | Solo logs (`LoggerForgotPasswordEmailSender`), `MAILER_DSN=null://null` | Provider real pendiente |
| BD de test | `.env.test` sin `DATABASE_URL` | Crear `orbis_test` |

---

## Fuentes de verdad

- `AGENTS.md` (reglas, patrones, apéndices A/C/D/E, key decisions)
- `CONTEXT.md` (estado de sesión + estado real por BC)
- `docs/ai-protocol.md` (protocolo de trabajo IA-humano)
- `docs/history.md` (historial, deuda técnica e inventario de reportes/importers)
- `composer.json` y `composer.lock` (dependencias exactas)
- `config/packages/doctrine.yaml`, `config/packages/messenger.yaml` (persistencia + mensajería)
- `config/services.yaml` (bindings interface→implementation)
- `.env.example` (DATABASE_URL, MAILER_DSN, MESSENGER_TRANSPORT_DSN, JWT keys)
- `compose.yaml` + `compose.override.yaml` (infra)
- `importmap.php`, `package.json`, `assets/controllers.json`, `assets/controllers/` (frontend)
- `docs/adr/README.md` + ADR-001..008, 011, 012, 013
- `docs/modules/*.md` (módulos por BC)
- Verificación en disco: `Get-ChildItem src/app`, `src/`, `config/`, `migrations/`, `templates/`, `tests/`, `docs/`
