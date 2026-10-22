# Dependencias de Orbis

> **Documento:** Inventario de dependencias backend, frontend e infraestructura (verificado 2026-08-06)
> **Fuente primaria:** `composer.json`, `package.json`, `importmap.php`, `compose.yaml`, `.env.example`, `config/packages/doctrine.yaml`

---

## 1. Runtime

| Componente | Versión | Fuente |
|------------|---------|--------|
| PHP | `>=8.4` | composer.json |
| Extensiones requeridas | `ext-ctype`, `ext-iconv` | composer.json |
| Base de datos | MySQL `8.0` (db: `cmscomec_orbis`) | `.env.example` `DATABASE_URL` |
| Framework | Symfony `8.0.*` (`symfony/symfony` en conflicto) | composer.json |

---

## 2. Backend — dependencias de producción (`composer.json` → `require`)

### 2.1 Framework Symfony

| Librería | Versión | Para qué se usa |
|----------|---------|-----------------|
| `symfony/framework-bundle` | 8.0.* | Núcleo del framework (kernel, DI, routing, config) |
| `symfony/console` | 8.0.* | Comandos CLI (`bin/console`, commands de consola por BC) |
| `symfony/dotenv` | 8.0.* | Carga de variables de entorno (`.env`) |
| `symfony/runtime` | 8.0.* | Entry point runtime (`public/index.php`) |
| `symfony/yaml` | 8.0.* | Parsing YAML (config, specs tooling) |
| `symfony/serializer` | 8.0.* | Serialización de respuestas (API) |
| `symfony/asset` | 8.0.* | Gestión de assets en templates |
| `symfony/flex` | ^2.11 | Recetas y autogeneración de config |
| `symfony/scheduler` | 8.0.* | Tareas programadas (`src/Schedule.php`) |

### 2.2 Doctrine / Persistencia

| Librería | Versión | Para qué se usa |
|----------|---------|-----------------|
| `doctrine/doctrine-bundle` | ^3.2.4 | Integración Doctrine con Symfony (DBAL + ORM) |
| `doctrine/orm` | ^3.6.7 | ORM (documentación previa decía "ORM 2.x" — desactualizado) |
| `doctrine/doctrine-migrations-bundle` | >=4 | Migraciones (`migrations/`, 31 versiones) |
| `symfony/doctrine-messenger` | 8.0.* | Transporte Messenger sobre Doctrine (colas `events`/`notifications`/`failed`) |
| `symfony/uid` | 8.0.* | Generación de ULIDs (IDs de 26 chars) |

### 2.3 Auth / Seguridad / MFA

| Librería | Versión | Para qué se usa |
|----------|---------|-----------------|
| `symfony/security-bundle` | 8.0.* | Seguridad: firewalls `api_jwt`, `api_public`, `api`; `#[IsGranted]` |
| `lexik/jwt-authentication-bundle` | >=3.2 | JWT API auth (RSA keys en `config/jwt/`) |
| `spomky-labs/otphp` | ^11.5 | TOTP para MFA (2FA setup/check) |
| `endroid/qr-code` | ^6.1.3 | Generación de QR (setup de MFA) |
| `ramsey/uuid` | >=4.9.3 | ⚠️ Declarada en composer.json; el proyecto usa **ULID** (`symfony/uid`). TODO: requiere validación de uso real |

### 2.4 Reportes / Importers / Exportación

| Librería | Versión | Para qué se usa |
|----------|---------|-----------------|
| `phpoffice/phpspreadsheet` | ^3.10.6 | Excel: exportación (`ExcelExportService` en Shared) e **importers** (11 implementados: clientes, terminales, modelos, productos, stock, empleados, usuarios, PO, gastos, partes de modelo) |
| `dompdf/dompdf` | ^3.1.5 | PDF: reportes (`PdfExportService` en Shared; 14 reportes implementados) |

### 2.5 Frontend / Templates

| Librería | Versión | Para qué se usa |
|----------|---------|-----------------|
| `symfony/twig-bundle` | 8.0.* | Motor de templates Twig (124 archivos en `templates/`) |
| `twig/extra-bundle` | >=3.24 | Extensiones Twig extra |
| `symfony/ux-turbo` | ^3.2 | Turbo Drive/Stream (navegación SPA-like sin JS custom) |
| `symfonycasts/tailwind-bundle` | ^0.12.0 | Compilación Tailwind CSS |

### 2.6 Messaging / Mailer

| Librería | Versión | Para qué se usa |
|----------|---------|-----------------|
| `symfony/messenger` | 8.0.* | Bus de mensajes (Commands sync + eventos async cross-BC) |
| `symfony/mailer` | 8.0.* | Envío de emails (`MAILER_DSN=null://null` en dev; Mailpit en override; forgot-password stubeado a Logger) |

---

## 3. Backend — dependencias de desarrollo (`require-dev`)

| Librería | Versión | Para qué se usa |
|----------|---------|-----------------|
| `phpunit/phpunit` | 11 | Framework de tests (452 test files según CONTEXT.md) |
| `symfony/browser-kit` | 8.0.* | Tests funcionales (cliente HTTP interno) |
| `symfony/css-selector` | 8.0.* | Selección CSS en tests funcionales |
| `symfony/dom-crawler` | 8.0.* | Crawling de respuestas HTML en tests |

**Config de tests:** `phpunit.xml` / `phpunit.dist.xml`; suites Unit / Integration / Functional (tests/).

---

## 4. Frontend

### 4.1 `package.json` (verificado)

```json
{
  "dependencies": {
    "better-sqlite3": "^12.10.1"
  }
}
```

⚠️ Única dependencia npm: `better-sqlite3` — no hay Vite/Webpack/ESLint/Prettier. El frontend corre sobre **Asset Mapper + importmap nativo** del navegador.

### 4.2 `importmap.php` (verificado)

| Entry | Path | Tipo |
|-------|------|------|
| `app` | `./assets/app.js` | entrypoint |
| `@hotwired/stimulus` | `vendor/@hotwired/stimulus/stimulus.index.js` | módulo |
| `@symfony/stimulus-bundle` | `./vendor/symfony/stimulus-bundle/assets/dist/loader.js` | módulo |
| `@hotwired/turbo` | `vendor/@hotwired/turbo/turbo.index.js` | módulo |

### 4.3 Stimulus controllers (`assets/controllers/`)

`csrf_protection`, `dashboard`, `hello`, `modal` (reemplaza `confirm()`), `module_finding`, `multi_item_form`, `notification_badge`, `prevent_double_submit`, `table_filter`, `tree` — registrados en `assets/controllers.json` junto con `@symfony/ux-turbo` (turbo-core eager).

> ⚠️ Documentación previa mencionaba **Alpine.js**; no está en `importmap.php` ni en `package.json`. TODO: requiere validación.

---

## 5. Infraestructura (`compose.yaml` + `.env.example`)

| Servicio | Imagen | Puerto | Nota |
|----------|--------|--------|------|
| `database` | `postgres:16-alpine` | (override: 5432) | ⚠️ Scaffold por defecto de Doctrine — la DB real según `.env.example` es **MySQL 8.0** (`cmscomec_orbis`) |
| `mailer` (override) | `axllent/mailpit` | 1025 (SMTP) / 8025 (UI) | Captura de correos en dev |

### Variables de entorno clave (`.env.example`)

| Variable | Valor | Uso |
|----------|-------|-----|
| `DATABASE_URL` | `mysql://root:change-me@127.0.0.1:3306/cmscomec_orbis?serverVersion=8.0&charset=utf8mb4` | Conexión MySQL 8 |
| `MAILER_DSN` | `null://null` | Mailer stubeado (dev) |
| `MAILER_FROM_EMAIL` / `MAILER_FROM_NAME` | `no-reply@orbis.local` / `Orbis Sistema` | Remitente |
| `MESSENGER_TRANSPORT_DSN` | `doctrine://default?auto_setup=0` | Colas sobre Doctrine |
| `JWT_SECRET_KEY` / `JWT_PUBLIC_KEY` | `%kernel.project_dir%/config/jwt/private.pem` / `public.pem` | RSA keys JWT |
| `JWT_PASSPHRASE` | `change-me` | Passphrase de la key |
| `APP_ENV` / `APP_DEBUG` | `dev` / `1` | Entorno |
| `APP_SHARE_DIR` | `var/share` | Directorio compartido |

---

## 6. Tipos custom DBAL (152 registrados en `config/packages/doctrine.yaml`)

Conteo verificado por BC (regex sobre doctrine.yaml):

| BC | Tipos | Ejemplos |
|----|-------|----------|
| **Identity** | 18 | `user_id`, `username`, `hashed_password`, `token_hash`, `user_status`, `user_token_type`, `auth_event`, `refresh_token_id` |
| **Client** | 28 | `client_id`, `client_ruc`, `contact_email`, `terminal_serial`, `terminal_assignment_status`, `contract_status`, `warranty_type`, `atm_model_code`, `site_id` |
| **Organization** | 17 | `employee_id`, `employee_identification_number`, `employee_status`, `employee_profile`, `employee_special_period_type`, `service_zone_status`, `vacation_request_status`, `overtime_status` |
| **Inventory** | 42 | `product_id`, `product_tracking_type`, `serialized_stock_id`, `stock_serial_number`, `movement_id`, `purchase_order_id`, `entry_id`, `repair_id`, `transformation_id`, `request_id`, `physical_count_id`, `inventory_*_status` (enums) |
| **Support** | 28 | `ticket_id`, `journey_id`, `ticket_finding_id`, `ticket_spare_part_id`, `support_ticket_status`, `support_service_type`, `module_node_id`, `rma_record_id`, `scheduled_maintenance_id` |
| **Location** | 3 | `geo_region_id`, `geo_province_id`, `geo_city_id` |
| **Expense** | 8 | `expense_operating_id`, `expense_item_id`, `expense_cash_advance_id`, `expense_status`, `expense_settlement_direction` |
| **Notification** | 7 | `notification_template_id`, `notification_log_id`, `notification_channel`, `notification_status`, `notification_event_type` |
| **Shared** | 1 | `processed_command_id` |
| **Total** | **152** | — |

**Patrón base (Shared Kernel):**
- `AbstractUlidType` — base para tipos de IDs ULID (VARCHAR 26)
- `AbstractStringValueObjectType` — base para tipos de ValueObjects string
- `AbstractStringEnumType` — base para enums `: string` (obligatorio: prohibido `type="string"` en ORM)

---

## 7. Servicios clave del Shared Kernel

Verificado con `Get-ChildItem src/app/Shared/` (44 archivos PHP):

| Servicio / Contrato | Archivo | Binding (services.yaml) |
|---------------------|---------|--------------------------|
| `TransactionManagerInterface` | `Application/Service/` | → `DoctrineTransactionManager` |
| `AbstractUlid` | `Domain/AbstractUlid.php` | base de todos los ULIDs |
| `IdGeneratorInterface` | `Domain/Service/` | → `UlidGenerator` |
| `DomainEventPublisherInterface` | `Domain/Event/` | → `SymfonyDomainEventPublisher` |
| `DomainEvent` (abstracto) | `Domain/Event/` | publicado por entidades (pullDomainEvents) |
| `DomainEventMessage` | `Infrastructure/Messenger/Message/` | envoltura Messenger → `async_events` |
| `DomainEventBridgeSubscriber` | `Infrastructure/Messenger/EventListener/` | puente events → async (patrón ADR-012) |
| `ProcessedCommandRepositoryInterface` | `Domain/Repositories/` | → `DoctrineProcessedCommandRepository` (idempotencia) |
| `IdempotencyService` / `IdempotentHandlerTrait` | `Application/Service/` + `Application/` | deduplicación de commands |
| `ReportGeneratorInterface` | `Application/Report/` | contrato de los 14 reportes |
| `FileParserInterface` + `CsvFileParser` | `Application/Import/` + `Infrastructure/Import/` | parsing de archivos de importers |
| `RowValidatorInterface` / `ImportResult` | `Application/Import/` | validación de filas y resultado |
| `ExcelExportService` / `PdfExportService` | `Infrastructure/Export/` | exportación Excel (PhpSpreadsheet) y PDF (dompdf) |
| `DomainException` / `ApplicationException` | `Exceptions/` | excepciones base (abstractas) |
| `DomainExceptionListener` | `Infrastructure/Symfony/EventListener/` | respuesta JSON de error consistente |
| `TimestampsSubscriber` + `TimestampableInterface` | `Infrastructure/Doctrine/` + `Domain/Contracts/` | auditoría created/updated |
| `PaginatedResult` | `Application/Query/` | envoltura de listas paginadas |
| `DashboardMetrics` / `DashboardMetricsHandler` | `Application/Query/Dashboard/` | métricas de dashboards |
| `StringNormalizer` | `Domain/` | normalización de strings (slugs, búsquedas) |
| `CrossBcConsistencyCheckCommand` | `Infrastructure/Console/` | verificación de consistencia cross-BC |
| `CleanupReportsCommand` | `Presentation/Console/` | limpieza de `var/reports` (`%app.reports_dir%`) |
| `OrbisFormatExtension` | `Presentation/Twig/Extension/` | filtros Twig de formato |
| `HomeController` / `DashboardController` | `Presentation/Http/` | páginas base |

### Otros bindings relevantes (config/services.yaml)

- **Repositorios**: cada `{BC}\Domain\Repositories\{X}RepositoryInterface` → `{BC}\Infrastructure\Persistence\Doctrine\Doctrine{X}Repository` (listado completo en services.yaml, sección "Interface – Implementation bindings")
- **Cross-BC resolvers**: `CrossBcUserEmailResolver`, `CrossBcContactFinder`, `CrossBcUserByEmailResolver`, `CrossBcEmployeeResolver`, `CrossBcUserResolver`, `CrossBcTicketResolver`
- **Scoping**: `MovementScopeResolverInterface` → `MovementViewScopeResolver`; `SparePartViewScopeResolver`
- **Notificaciones**: `ContactResolverInterface` → `ChainContactResolver` con `!tagged_iterator app.notification_contact_resolver` (Organization/Client/User/Location resolvers con tag)
- **Console**: `SeedModuleTreeCommand`, `SeedGeographyCommand`, `ProcessEmployeeSpecialPeriodsCommand`, `CrossBcConsistencyCheckCommand`, `CleanupReportsCommand` (autoconfigurados con tag `console.command`)

---

## Fuentes de verdad

- `composer.json` (dependencias exactas backend)
- `package.json` + `package-lock.json` (frontend npm)
- `importmap.php` + `assets/controllers.json` + `assets/controllers/` (frontend runtime)
- `compose.yaml` + `compose.override.yaml` (infra)
- `.env.example` (variables de entorno)
- `config/packages/doctrine.yaml` (152 DBAL types, conteo por regex)
- `config/services.yaml` (bindings interface → implementation)
- `config/packages/messenger.yaml` (transports y routing)
- Verificación en disco: `Get-ChildItem src/app/Shared/` (44 archivos)
- `CONTEXT.md` §4/§5 (reportes e importers implementados)
