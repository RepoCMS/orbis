# Estructura de Carpetas de Orbis

> **Documento:** Árbol de directorios completo y verificado en disco
> **Fecha:** 2026-08-06
> **Método:** `Get-ChildItem` sobre el repositorio + `CONTEXT.md` §9 como base

---

## 1. Raíz del proyecto

```
orbis/
├── .ai/                      # ⚠️ Directorio VACÍO (0 archivos) — reservado para tooling AI
├── .claude/                  # Coordinación Claude Code (worktrees de agentes: .claude/worktrees/agent-*/)
├── .codegraph/               # Índice CodeGraph (tree-sitter knowledge graph)
├── .github/                  # copilot-instructions.md + agents/prompts speckit (10 + 10)
├── .opencode/                # opencode.jsonc (MCP codegraph) + agent/ (7 roles)
├── .specify/                 # Speckit: templates/, scripts/powershell/, memory/, integrations/
├── .vscode/                  # settings.json
├── .phpunit.cache/           # Cache de PHPUnit
├── assets/                   # JS (Stimulus/Turbo), estilos, vendor
├── bin/                      # Console Symfony
├── config/                   # Configuración Symfony (packages, routes, services)
├── docs/                     # Documentación (adr/, modules/, api/, bc/, runbooks/)
├── migrations/               # 31 migraciones Doctrine (Version2026*.php)
├── public/                   # Document root (index.php, assets instalados)
├── specs/                    # 14 specs por BC (spec.md + tasks.md)
├── src/                      # Código: app/ (BCs) + legacy raíz
├── tasks/                    # ⚠️ Directorio VACÍO (0 archivos)
├── templates/                # 124 templates Twig
├── tests/                    # Unit / Integration / Functional
├── var/                      # Cache, logs, reports (app.reports_dir)
├── AGENTS.md                 # Reglas de código (SOLO reglas; estado vive en CONTEXT.md)
├── CHANGELOG.md
├── CLAUDE.md                 # Protocolo de coordinación entre herramientas
├── CONTEXT.md                # ⚠️ ÚNICA fuente de verdad de estado
├── HANDOFF.md                # Protocolo handoff entre agentes
├── PROJECT_CONTEXT.md        # Contexto AI-native del repositorio
├── TODOS.md                  # ⚠️ DEPRECADO → tablero en tasks/ (TODO.md, IN_PROGRESS.md, DONE.md)
├── composer.json / composer.lock
├── importmap.php             # Importmap frontend (Stimulus, Turbo, app)
├── package.json / package-lock.json   # Solo better-sqlite3 (dev)
├── phpunit.dist.xml / phpunit.xml
├── symfony.lock
├── validate_project.ps1      # Validación general (reportes + importers + esenciales)
├── validate_reports.ps1 / validate_reports.sh
├── validate_importers.ps1 / validate_importers.sh
├── compose.yaml              # ⚠️ PostgreSQL 16 (scaffold default; DB real = MySQL 8)
├── compose.override.yaml     # Puertos DB + Mailpit (SMTP 1025 / UI 8025)
├── .env / .env.dev / .env.test / .env.example / .env.local
├── constitution.md           # Principios del repositorio
└── .editorconfig / .gitignore / .phpactor.json
```

---

## 2. `src/` — raíz

```
src/
├── Kernel.php                # Kernel Symfony (config de rutas y servicios)
├── Schedule.php              # Definición de tareas programadas (symfony/scheduler)
├── Controller/               # ⚠️ LEGACY — solo contiene .gitignore (sin código)
├── Entity/                   # ⚠️ LEGACY — solo contiene .gitignore
├── Repository/               # ⚠️ LEGACY — solo contiene .gitignore
└── app/                      # ← CÓDIGO REAL (9 directorios, 1863 archivos PHP en total src/)
    ├── Client/               #   294 php · 8 mappings XML
    ├── Expense/              #   139 php · 3 mappings XML
    ├── Identity/             #   252 php · 8 mappings XML
    ├── Inventory/            #   459 php · 15 mappings XML
    ├── Location/             #    37 php · 3 mappings XML
    ├── Notification/         #    96 php · 3 mappings XML
    ├── Organization/         #   216 php · 7 mappings XML
    ├── Shared/               #    44 php · 0 mappings XML (dir Mapping vacío)
    └── Support/              #   324 php · 19 mappings XML
```

> ⚠️ `App\DeviceCatalog` está en el autoload de composer.json pero `src/app/DeviceCatalog/` **no existe** en disco (BC SUPERSEDED).

---

## 3. Plantilla estándar por BC

Fuente: `CONTEXT.md` §9 — verificada contra `src/app/` (todos los BCs siguen este patrón; Client, Expense, Identity, Inventory, Location, Notification, Organization, Support tienen las 4 capas; Shared varía ligeramente — ver §4.9).

```
src/app/{BC}/
├── Application/
│   ├── Command/{Action}{Resource}/
│   │   ├── {Action}{Resource}Command.php      (final readonly class)
│   │   └── {Action}{Resource}Handler.php      (__invoke)
│   ├── Query/{Find|List}{Resource}/
│   │   ├── {Find|List}{Resource}Query.php
│   │   └── {Find|List}{Resource}Handler.php
│   ├── Query/Response/{Resource}Response.php  (DTO, fromEntity())
│   ├── Service/{Resource}ViewScopeResolver.php (scoping, si aplica)
│   ├── Report/{Nombre}ReportGenerator.php      (si aplica)
│   └── Exception/{NotFoundException}.php       (si aplica)
├── Domain/
│   ├── Entities/{Entity}.php
│   ├── ValueObjects/{Name}ValueObject.php
│   ├── Enums/{Name}Enum.php / {Name}Status.php
│   ├── Repositories/{Resource}RepositoryInterface.php
│   ├── Events/{EventName}.php
│   └── Exceptions/{BC}DomainException.php
├── Infrastructure/
│   ├── Persistence/Doctrine/
│   │   ├── Doctrine{Resource}Repository.php
│   │   └── Mapping/{Entity}.orm.xml
│   ├── Doctrine/Types/{Field}Type.php
│   ├── Service/{IntegrationService}.php
│   ├── EventListener/{Listener}.php           (si aplica)
│   ├── Messenger/{Handler}.php                (si aplica)
│   └── Console/{Command}Command.php           (si aplica)
└── Presentation/
    ├── Http/
    │   ├── Api{BC}{Entity}Controller.php      (API)
    │   └── {BC}{Entity}Controller.php         (web)
    └── Console/{CommandName}Command.php       (si aplica)
```

---

## 4. Estructura real por BC

### 4.1 Identity (252 php) — ejemplo completo de Application/

```
src/app/Identity/
├── Application/
│   ├── Command/  (32 commands: ActivateRole, ActivateUser, AssignPermissionToRole,
│   │              AssignRoleToUser, ChangePassword, ChangePermissionDescription,
│   │              ChangeUsername, CreateRole, DeactivateRole, DeactivateUser,
│   │              DefinePermission, DeleteRole, DisableUserMfa, GenerateActivationToken,
│   │              GenerateBackupCodes, ImportUsers, InviteContactToPortal,
│   │              ReactivateUser, RecordFailedLogin, RegisterUser, RenameRole,
│   │              RequestPasswordReset, ResetPassword, RevokeMfa,
│   │              RevokePermissionFromRole, RevokeRefreshToken, SoftDeleteUser,
│   │              StoreRefreshToken, SyncRolePermissions, UnlockUser, VerifyUser,
│   │              VerifyUserByToken)          — cada uno: {X}Command.php + {X}Handler.php
│   ├── Exception/
│   ├── Query/  (13: FindPermissionById, FindPermissionByKey, FindPermissionsByIds,
│   │             FindRoleById, FindRoleByName, FindRoleBySlug, FindUserById,
│   │             FindUserByUsername, IdentityDashboardMetrics, ListAuthLog,
│   │             ListPermissions, ListRoles, ListUsers)
│   ├── Query/Response/  (DTOs)
│   └── Report/SecurityEvents/  (SecurityEventsReportGenerator)
├── Domain/
│   ├── Entities/  (User, Role, Permission, UserToken, AuthLog, UserMfaConfig,
│   │               UserMfaBackupCode, RefreshToken)
│   ├── Enums/     (UserStatus, RoleStatus, UserTokenType, AuthEvent, ...)
│   ├── Events/
│   ├── Exceptions/
│   ├── Repositories/  (UserRepositoryInterface, RoleRepositoryInterface,
│   │                   PermissionRepositoryInterface, UserTokenRepositoryInterface,
│   │                   AuthLogRepositoryInterface, UserMfaConfigRepositoryInterface,
│   │                   UserMfaBackupCodeRepositoryInterface, RefreshTokenRepositoryInterface)
│   └── Service/       (PasswordHasherInterface, AccountNotifierInterface, ...)
├── Infrastructure/
│   ├── Doctrine/Types/   (18 tipos: UserIdType, RoleIdType, UsernameType,
│   │                      HashedPasswordType, UserStatusType, AuthEventType, ...)
│   ├── Persistence/Doctrine/
│   │   ├── Doctrine{User|Role|Permission|UserToken|AuthLog|UserMfaConfig|...}Repository.php
│   │   └── Mapping/ (8 .orm.xml)
│   ├── Security/  (JWT handlers, MFA)
│   └── Service/   (NativePasswordHasher, SymfonyMailerAccountNotifier,
│                   CrossBcUserEmailResolver, CrossBcContactFinder, ...)
└── Presentation/
    ├── Http/  (15 controllers: ApiIdentityUserController, ApiIdentityRoleController,
    │           ApiIdentityAuthController, IdentityDashboardController, ...)
    └── Console/  (seed permissions/roles/admin, cleanup refresh tokens)
```

### 4.2 Client (294 php)

```
Application/ → Command/ (37: ImportClients, ImportTerminals, ImportModels, CreateClient,
                UpdateClient, CreateContact, ...), Query/ (16 + Response/),
                Report/{ContractsExpiring, TerminalsByClient}/
Domain/      → Entities/ (Client, Contact, Terminal, TerminalAssignment, TerminalWarranty,
                TerminalModel, ModelPart, Contract, Site), Enums/, Events/, Exceptions/,
                Repositories/ (9 interfaces), ValueObjects/
Infrastructure/ → Doctrine/Types/ (28 tipos: client_id, client_ruc, contact_email,
                terminal_serial, contract_status, atm_model_code, site_id, ...),
                Persistence/Doctrine/ (Doctrine{Client|Contact|Terminal|...}Repository.php,
                Mapping/ 8 .orm.xml), Service/, Messenger/ (handlers de importación async)
Presentation/ → Http/ (18 controllers)
```

### 4.3 Expense (139 php) — 100%

```
Application/ → Command/ (15: CreateOperatingExpense, RejectExpenseItem,
                SubmitExpenseSettlement, CreateCashAdvance, ...), Query/ (8 + Response/),
                Report/{Expense, Advance}/, Service/ (EmployeeResolverInterface,
                UserResolverInterface, TicketResolverInterface — cross-BC)
Domain/      → Entities/ (OperatingExpense, ExpenseItem, CashAdvance), Enums/
                (ExpenseStatus, ExpenseItemType, CashAdvanceStatus, ...), Events/,
                Exceptions/, Repositories/, ValueObjects/
Infrastructure/ → Doctrine/Types/ (8), Persistence/Doctrine/ (3 repos + 3 mappings),
                Messenger/, Service/ (CrossBcEmployeeResolver, CrossBcUserResolver,
                CrossBcTicketResolver)
Presentation/ → Http/ (5 controllers)
```

### 4.4 Inventory (459 php) — el BC más grande

```
Application/ → Command/ (60: CreateTransfer, ConfirmTransfer, CancelTransfer,
                RejectTransfer, CreatePurchaseOrder, AddEntryItem, DefineStockMinimum,
                CreateRepair, CreateTransformation, CreateRequest, PhysicalCount, ...),
                Query/ (29 + Response/), Report/ (4: ValuedInventory, Movement,
                ResignationReturn, PhysicalCount), Service/ (StockViewScopeResolver,
                MovementViewScopeResolver)
Domain/      → Entities/ (Product, Location, SerializedStock, NonserializedStock,
                Movement, PurchaseOrder, PurchaseOrderItem, Entry, EntryItem, Repair,
                Transformation, Request, RequestItem, StockMinimum, SerialHistory,
                SubLocation, TechnicianLoan, PhysicalCount, PhysicalCountItem,
                RepairEvidence, Consumable), Enums/, Events/, Exceptions/, Repositories/, ValueObjects/
Infrastructure/ → Doctrine/Types/ (42), Persistence/Doctrine/ (22 repos + 15 mappings),
                Messenger/ (AutoCreateBackpackHandler, ...), Service/
Presentation/ → Http/ (28 controllers: ApiInventoryStockController, StockController,
                MovementController, EntryController, ProductController, RepairController,
                TransformationController, StockRequestController, ...)
```

### 4.5 Location (37 php) — API-only, solo lectura + seed

```
Application/ → Query/ (6: FindCityById, ListCities, ListCitiesForProvince,
                ListProvinces, ListRegions, SearchCities), Query/Response/ (4 DTOs),
                Exception/
Domain/      → Entities/ (Region, Province, City), ValueObjects/ (3 IDs),
                Repositories/ (3 interfaces), Exceptions/
Infrastructure/ → Console/SeedGeographyCommand.php, Doctrine/Types/ (3),
                Persistence/Doctrine/ (3 repos + 3 mappings)
Presentation/ → Http/ApiLocationController.php  (único controller)
```

### 4.6 Notification (96 php)

```
Application/ → Command/ (8: SendNotification, ...), Query/ (5 + Response/), Service/
Domain/      → Entities/ (NotificationTemplate, NotificationLog, NotificationPreference),
                Enums/ (NotificationChannel, NotificationStatus, EventType, RecipientType),
                Events/, Exceptions/, Repositories/, Services/ (ContactResolverInterface)
Infrastructure/ → Channel/ (EmailChannelSender, WhatsApp stub Logger-only),
                Doctrine/Types/ (7), Persistence/Doctrine/ (3 repos + 3 mappings),
                Security/ (SecurityUserResolver), Service/ (OrganizationContactResolver,
                ClientContactResolver, UserContactResolver, LocationContactResolver,
                ChainContactResolver — tagged resolvers), EventListener/
Presentation/ → Http/ (8 controllers)
```

### 4.7 Organization (216 php)

```
Application/ → Command/ (33: CreateEmployee, UpdateEmployee, DeactivateEmployee,
                ReactivateEmployee, SoftDeleteEmployee, LinkUserToEmployee,
                ProvisionEmployeeAccess, CreateServiceZone, AssignTechnicianToZone,
                ScheduleEmployeeVacation, ScheduleEmployeeStandby, AddCityToZone, ...),
                Query/ (8 + Response/), Report/ (3: ActivePersonnel, Overtime,
                ScheduledVacations), Service/ (TerminationVerificationService)
Domain/      → Entities/ (Employee, EmployeeSpecialPeriod, EmploymentPeriod, ServiceZone,
                VacationRequest, OvertimeRecord, ServiceZoneAssignment), Enums/
                (EmployeeProfile, EmployeeStatus, EmployeeSpecialPeriodType, ...),
                Events/ (EmployeeCreated, EmployeeDeactivated, EmployeeAccessProvisioned, ...),
                Exceptions/, Repositories/, ValueObjects/
Infrastructure/ → Doctrine/Types/ (17), Persistence/Doctrine/ (7 repos + 7 mappings),
                Console/ (ProcessEmployeeSpecialPeriodsCommand), EventListener/
                (UserStatusCascadeOnEmployeeChangeListener), Messenger/, Service/
Presentation/ → Http/ (8 controllers)
```

### 4.8 Support (324 php) — incluye ATM Module Tree (013)

```
Application/ → Command/ (35: CreateTicket, AssignTicket, CancelTicket, ApproveTicket,
                PauseTicket, ResumeTicket, RescheduleTicket, StartOutwardJourney,
                ArriveAtSite, ArriveAtBase, StartWaiting, StartAssistanceFromWaiting,
                CompleteAssistance, RegisterFinding, RecordModuleFinding,
                UploadFindingEvidence, UpdateModuleFinding, ...),
                Query/ (20: GetTicketDetail, ListTickets, ListTicketModuleFindings,
                ListTicketSpareParts, GetAtmModuleTree, ... + Response/),
                Report/ (2: Technician, TicketsByClient), Service/ (SparePartViewScopeResolver)
Domain/      → Entities/ (Ticket, TicketAssignment, Journey, TicketFinding, TicketSparePart,
                TicketPendingSparePart, TicketEvidence, TicketPause, TicketReschedule,
                TicketWaiting, TicketAuditLog, QuickTemplate, ...),
                Enums/ (TicketStatus 11 estados, ServiceType, JourneyType, ...),
                Events/ (TicketCreated, TicketAssigned, TicketClosed, ...),
                Exceptions/, Repositories/ (17+), ValueObjects/, AtmModel/ (AtmModuleTreeRegistry)
Infrastructure/ → Console/ (SeedModuleTreeCommand), Doctrine/Types/ (28),
                Persistence/Doctrine/ (19 repos + 19 mappings), Service/
Presentation/ → Http/ (12 controllers: ApiSupportTicketController,
                ApiSupportDashboardController, TicketController, TicketActionController, ...)
                Console/ (SeedModuleTreeCommand)
```

### 4.9 Shared Kernel (44 php)

```
src/app/Shared/
├── Application/
│   ├── IdempotentHandlerTrait.php
│   ├── Import/        (FileParserInterface, ImportResult, RowValidatorInterface)
│   ├── Query/         (PaginatedResult, Dashboard/DashboardMetrics + Handler)
│   ├── Report/        (ReportGeneratorInterface)
│   └── Service/       (TransactionManagerInterface, IdempotencyService + Interface)
├── Domain/
│   ├── AbstractUlid.php            ← base de todos los ULIDs
│   ├── StringNormalizer.php
│   ├── Contracts/TimestampableInterface.php
│   ├── Entities/ProcessedCommand.php
│   ├── Event/         (DomainEvent, DomainEventPublisherInterface)
│   ├── Repositories/  (ProcessedCommandRepositoryInterface)
│   ├── Service/       (IdGeneratorInterface)
│   └── ValueObjects/  (ProcessedCommandId + InvalidIdException)
├── Exceptions/        (DomainException abstracta, ApplicationException)
├── Infrastructure/
│   ├── Console/       (CrossBcConsistencyCheckCommand)
│   ├── Doctrine/      (EventSubscriber/TimestampsSubscriber,
│   │                   Types/AbstractStringEnumType, AbstractStringValueObjectType,
│   │                   AbstractUlidType, ProcessedCommandIdType)
│   ├── Event/         (SymfonyDomainEventPublisher)
│   ├── Export/        (ExcelExportService, PdfExportService)
│   ├── Import/        (CsvFileParser)
│   ├── Messenger/     (EventListener/DomainEventBridgeSubscriber,
│   │                   Message/DomainEventMessage)
│   ├── Persistence/Doctrine/ (DoctrineProcessedCommandRepository,
│   │                   DoctrineTransactionManager, Mapping/ ← vacío)
│   ├── Service/       (UlidGenerator)
│   └── Symfony/       (EventListener/DomainExceptionListener)
└── Presentation/
    ├── Console/       (CleanupReportsCommand)
    ├── Http/          (DashboardController, HomeController)
    └── Twig/Extension/ (OrbisFormatExtension)
```

---

## 5. `config/`

```
config/
├── packages/
│   ├── asset_mapper.yaml          # Asset Mapper (frontend sin bundler)
│   ├── cache.yaml
│   ├── doctrine.yaml              # 152 DBAL types + 9 mappings XML + naming strategy
│   ├── doctrine_migrations.yaml
│   ├── framework.yaml
│   ├── lexik_jwt_authentication.yaml  # JWT API auth
│   ├── mailer.yaml
│   ├── messenger.yaml             # async_events / async_notifications / failed / sync
│   ├── property_info.yaml
│   ├── routing.yaml
│   ├── security.yaml              # Firewalls: api_jwt, api_public, api (stateless)
│   ├── symfonycasts_tailwind.yaml
│   ├── twig.yaml
│   └── ux_turbo.yaml
├── routes/
│   ├── framework.yaml
│   └── security.yaml
│   # ⚠️ NO hay archivos de rutas por BC — las rutas se declaran con atributos
│   #    #[Route] en los controllers (src/app/{BC}/Presentation/Http/)
├── bundles.php
├── preload.php
├── reference.php
├── routes.yaml
├── services.yaml                 # Bindings interface → implementation por BC
└── jwt/                          # private.pem / public.pem (RSA, según .env.example)
```

---

## 6. `templates/` (124 archivos)

```
templates/
├── base.html.twig
├── components/          (_macros.html.twig, _table_toolbar.html.twig)
├── dashboard/           (index.html.twig)
├── email/               (activation.html.twig, ...)
├── verification/        (success.html.twig, error.html.twig)
├── security/            (login.html.twig, 2fa_check.html.twig, 2fa_setup.html.twig)
├── user/  role/  employee/  service_zone/        (CRUD: list/form/show)
├── client/  (incl. contacts/)   site/
├── inventory/  (product, location, movement, entry, purchase_order, repair,
│                request, transformation, stock/overview)
├── support/  (ticket/ list-form-show)
├── expense/  organization/  notification/        (verificados en disco)
```

---

## 7. `assets/` (frontend)

```
assets/
├── app.js                     # Entrypoint (importmap)
├── stimulus_bootstrap.js
├── controllers.json           # ux-turbo (turbo-core eager)
├── controllers/
│   ├── csrf_protection_controller.js
│   ├── dashboard_controller.js
│   ├── hello_controller.js
│   ├── modal_controller.js        # reemplaza confirm() del navegador
│   ├── module_finding_controller.js
│   ├── multi_item_form_controller.js
│   ├── notification_badge_controller.js
│   ├── prevent_double_submit_controller.js
│   ├── table_filter_controller.js
│   └── tree_controller.js
├── styles/app.css            # Tailwind
└── vendor/                   # @hotwired/stimulus, @hotwired/turbo (instalados)
```

---

## 8. `migrations/`, `specs/`, `tests/`

```
migrations/          # 31 archivos Version202604*.php (formato VersionYYYYMMDDHHMMSS)
specs/               # 14 specs (cada una: spec.md + tasks.md)
├── 001-identity-spec / 001-organization-spec / 002-client / 003-support /
│   004-inventory / 005-location / 006-notification / 007-expense / 009-cross-bc /
│   010a-employee-user-invariant / 010b-sparepart-request-scope /
│   011-notifications-stock-requests / 012-multi-item-request / 013-atm-module-tree
tests/
├── Unit/            # Client, Expense, Identity, Inventory, Location, Notification,
│                    # Organization, Shared, Support (espejo de src/app, capa por capa)
├── Integration/     # Client, Inventory
└── Functional/      # Client
```

---

## 9. `docs/`

```
docs/
├── adr/                 # ADR-001..008.md + 011-transaction-manager-interface.md +
│                        # 012-cross-bc-event-cascade.md + 013-table-prefixes.md + README.md
├── modules/             # client.md, expense.md, identity.md, inventory.md,
│                        # location.md, notification.md, organization.md, support.md
├── api/                 # ⚠️ VACÍO
├── bc/                  # ⚠️ VACÍO
├── runbooks/            # ⚠️ VACÍO
├── analytics.md         # Dashboards / reportes / importers
├── history.md           # Issues y deuda técnica
├── architecture.md      # ← este documento
├── folder-structure.md  # ← este documento
└── dependencies.md      # ← este documento
```

---

## 10. Directorios AI y automatización

```
.ai/                     # ⚠️ VACÍO (sin archivos) — TODO: definir propósito
tasks/                   # ⚠️ VACÍO (sin archivos) — TODO: definir propósito
.opencode/
├── opencode.jsonc       # Configuración opencode (MCP codegraph local)
└── agent/               # planner.md, qa-reviewer.md, developer.md, test-engineer.md,
                         # frontend.md, security-reviewer.md, docs.md
.github/
├── copilot-instructions.md
├── agents/              # speckit.{analyze,checklist,clarify,constitution,implement,
│                        #   plan,specify,tasks,taskstoissues}.agent.md (10)
└── prompts/             # speckit.*.prompt.md (10)
.specify/
├── extensions.yml, integration.json
├── integrations/        # copilot.manifest.json, speckit.manifest.json
├── memory/constitution.md
├── scripts/powershell/  # check-prerequisites, common, create-new-feature,
│                        # setup-plan, setup-tasks
└── templates/           # spec/plan/tasks/checklist/constitution templates
.claude/
└── worktrees/agent-*/   # Worktrees de agentes Claude Code (código espejo)
```

---

## 11. Notas y advertencias

| # | Nota |
|---|------|
| 1 | `src/Controller`, `src/Entity`, `src/Repository` son **legacy vacíos** (solo `.gitignore`) — el código vive en `src/app/{BC}/Presentation/Http/` |
| 2 | `config/routes/` solo contiene framework + security — las rutas se definen **por atributos** `#[Route]` en los controllers |
| 3 | `docs/api/`, `docs/bc/`, `docs/runbooks/` existen pero están **vacíos** |
| 4 | `.ai/` y `tasks/` existen pero están **vacíos** |
| 5 | `src/app/Shared/Infrastructure/Persistence/Doctrine/Mapping/` está configurado en doctrine.yaml pero **vacío** |
| 6 | Total PHP en `src/`: **1863** (Get-ChildItem 2026-08-06); CONTEXT.md reporta 1411 (CodeGraph) — la diferencia incluye archivos no indexados |

---

## Fuentes de verdad

- Verificación en disco: `Get-ChildItem` sobre `src/`, `src/app/*`, `config/`, `templates/`, `assets/`, `migrations/`, `specs/`, `tests/`, `docs/`, `.ai/`, `tasks/`, `.opencode/`, `.github/`, `.specify/`, `.claude/`
- `CONTEXT.md` §9 (plantilla de estructura por BC) y §8 (nomenclatura)
- `AGENTS.md` (patrones de archivo, apéndices A/E)
- `config/packages/doctrine.yaml` (mappings y tipos por BC)
- `config/services.yaml` (recursos por BC y exclusiones de Domain/)
- `PROJECT_CONTEXT.md` (estructura de capas 4+1)
