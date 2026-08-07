# Feature Specification: Client Management

**Feature Branch**: `002-client`

**Created**: 2026-06-17

**Última actualización**: 2026-08-07 (revisión T03 — alineación con código real verificada en disco + CodeGraph + `php bin/wiring-check.php`)

**Status**: In Development (~92% implementado)

---

## 1. Descripción General

El Bounded Context **Client** gestiona todo lo relacionado con los clientes de ACMS, sus terminales/cajeros automáticos, contactos, contratos, sitios de instalación, warranties, modelos de terminal y partes de modelo. Es el BC central que define la relación contractual con los bancos y otras instituciones financieras.

Responsabilidades:
- Registro y ciclo de vida de clientes (personas jurídicas)
- Gestión de contactos por cliente
- Vinculación de contactos con usuarios del sistema (Identity BC)
- Registro y ciclo de vida de terminales (cajeros automáticos)
- Asignación de terminales a sitios de clientes
- Contratos de servicio con SLAs
- Garantías (warranties) de terminales
- Catálogo de modelos de terminal y partes de modelo (árbol de repuestos)
- Dashboard de KPIs por cliente (11 KPIs, web + API)
- Reportes descargables Excel/PDF (contratos por vencer, terminales por cliente)
- Importación masiva CSV (clientes P1, terminales P1, modelos P2, partes de modelo P2)

---

## 2. Business Rules

| # | Regla | Descripción |
|---|-------|-------------|
| CL‑01 | **RUC único** | No pueden existir dos clientes con el mismo RUC (`ClientRucAlreadyExistsException`). |
| CL‑02 | **Contacto obligatorio** | Todo cliente debe tener al menos 1 contacto activo (excepto en cascada de desactivación). |
| CL‑03 | **Serial único** | El número de serie de un terminal debe ser único en el sistema (`SerialForTerminalAlreadyExistsException`). |
| CL‑04 | **Ciclo de vida cliente** | ACTIVE → INACTIVE. Desactivar un cliente inactiva sus terminales y cascada a contactos (vía handlers). |
| CL‑05 | **Vinculación contacto-usuario** | Un contacto puede vincularse a un User de Identity BC (`LinkUserToContact` / `UnlinkUserFromContact`). La desvinculación desactiva el User. |
| CL‑06 | **Asignación de terminal** | Un terminal puede asignarse a un sitio de un cliente. La asignación puede ser OPERATIVE o INOPERATIVE. |
| CL‑07 | **Warranty** | Garantía de fabricante (manufacturer) o extendida (extended). `CheckExpiringWarrantiesCommand` verifica vencimiento a 30 días. |
| CL‑08 | **Importación masiva** | Clientes y terminales se importan vía CSV (sync <100 filas; mensajes async registrados para volúmenes grandes). Por cada fila importada se publica un Domain Event (ClientCreated). |
| CL‑09 | **Contrato con SLA** | Los contratos definen alcance del servicio y SLAs (evaluados por Support BC). |
| CL‑10 | **Cascada desactivación** | Desactivar un cliente → contactos INACTIVE → users de Identity desactivados (⚠️ parcial — ver G‑04). |

---

## 3. Aggregates

### 3.1 Client (Aggregate Root) — `client_clients`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `ClientIdValueObject` (ULID) | Identificador único |
| `name` | `ClientNameValueObject` | Nombre (max 100 chars) |
| `ruc` | `ClientRucValueObject` | RUC Ecuador (11-13 dígitos) |
| `address` | `?string` | Dirección |
| `status` | `ClientStatus` | ACTIVE / INACTIVE |
| `createdAt` | `DateTimeImmutable` | Fecha creación |
| `updatedAt` | `?DateTimeImmutable` | Fecha actualización |
| `deletedAt` | `?DateTimeImmutable` | Soft delete |

**Factory:** `Client::register(id, name, ruc, address): self`

**State transitions:** `activate()`, `deactivate()`

### 3.2 Contact — `client_contacts`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `ContactIdValueObject` (ULID) | Identificador único |
| `clientId` | `ClientIdValueObject` | Cliente al que pertenece |
| `userId` | `?UserIdValueObject` | User vinculado (Identity BC) |
| `firstName`, `lastName` | `string` | Nombre completo |
| `email` | `ContactEmailValueObject` | Email |
| `phoneNumber` | `ContactPhoneNumberValueObject` | Teléfono 10 dígitos |
| `role` | `ContactRoleValueObject` | Rol del contacto |
| `status` | `ContactStatus` | ACTIVE / INACTIVE |
| `deletedAt` | `?DateTimeImmutable` | Soft delete |

**Factory:** `Contact::create(id, clientId, firstName, lastName, email, phoneNumber, role): self`

**State transitions:** `activate()`, `deactivate()`, `linkUser(userId)`, `unlinkUser()`

### 3.3 Terminal — `client_terminals`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `TerminalIdValueObject` (ULID) | Identificador único |
| `clientId` | `ClientIdValueObject` | Cliente propietario |
| `serial` | `SerialNumberValueObject` | Número de serie único |
| `vendor` | `TerminalVendor` | hyosung / diebold / ncr / grg |
| `funcionality` | `TerminalFunctionality` | dispenser / recycler / multinfunction / counter |
| `type` | `TerminalType` | lobby / ttw |
| `atmModelCode` | `AtmModelCodeValueObject` | Código de modelo ATM (referencia al catálogo) |
| `status` | `TerminalStatus` | ACTIVE / INACTIVE |
| `deletedAt` | `?DateTimeImmutable` | Soft delete |

**Factory:** `Terminal::add(id, clientId, serial, vendor, funcionality, type): self`

### 3.4 TerminalAssignment — `client_terminal_assignments`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `TerminalAssignmentIdValueObject` (ULID) | Identificador único |
| `terminalId` | `TerminalIdValueObject` | Terminal asignado |
| `name` | `TerminalNameValueObject` | Nombre de la asignación |
| `idAssignedByClient` | `TerminalIdAssignedByClientValueObject` | ID del cliente (max 25) |
| `siteId` | `SiteIdValueObject` | Sitio de instalación |
| `status` | `TerminalAssignmentStatus` | OPERATIVE / INOPERATIVE |
| `deletedAt` | `?DateTimeImmutable` | Soft delete |

### 3.5 Site — `client_sites`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `SiteIdValueObject` (ULID) | Identificador único |
| `clientId` | `ClientIdValueObject` | Cliente |
| `cityId` | `string` | Ciudad (Location BC) |
| `terminalId` | `?TerminalIdValueObject` | Terminal asociado |
| `name` | `string` | Nombre del sitio |
| `status` | `SiteStatus` | ACTIVE / INACTIVE |
| `serviceZoneId` | `?string` | Zona de servicio (Organization BC) |
| `deletedAt` | `?DateTimeImmutable` | Soft delete |

### 3.6 Contract — `client_contracts`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `ContractIdValueObject` (ULID) | Identificador único |
| `clientId` | `ClientIdValueObject` | Cliente |
| `startDate` | `DateTimeImmutable` | Fecha inicio |
| `endDate` | `?DateTimeImmutable` | Fecha fin (null = indefinido) |
| `terms` | `?string` | Términos |
| `scope` | `?string` | Alcance |
| `status` | `ContractStatus` | ACTIVE / EXPIRED / CANCELLED |

### 3.7 TerminalModel — `client_terminal_models`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `TerminalModelIdValueObject` (ULID) | Identificador único |
| `name` | `TerminalModelNameValueObject` | Nombre del modelo |
| `description` | `?string` | Descripción |
| `createdAt` | `DateTimeImmutable` | Fecha creación |

### 3.8 TerminalWarranty — `client_terminal_warranties`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `TerminalWarrantyIdValueObject` (ULID) | Identificador único |
| `terminalId` | `TerminalIdValueObject` | Terminal |
| `type` | `WarrantyType` | manufacturer / extended |
| `startDate` | `DateTimeImmutable` | Inicio |
| `endDate` | `DateTimeImmutable` | Fin |
| `terms` | `?string` | Términos |

### 3.9 ModelPart — `client_model_parts`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | ULID | Identificador único |
| `modelId` | `TerminalModelIdValueObject` | Modelo de terminal al que pertenece |
| `name` | `string` | Nombre de la parte |
| `partNumber` | `string` | Número de parte |
| `level` | `?string` | Nivel en el árbol de partes |
| `parentId` | `?ModelPartIdValueObject` | Parte padre (árbol jerárquico) |
| `createdAt` | `DateTimeImmutable` | Fecha creación |

---

## 4. Commands

**Total real: 37** — 35 sync (`{Action}{Resource}Command` + Handler) + 2 async (`ImportClientsMessage`, `ImportTerminalsMessage`).

| ID | Command | Handler / Responsabilidad | Disparado desde (ver §8) |
|----|---------|---------------------------|---------------------------|
| C‑01 | `RegisterClient` | Crea cliente. Guarda RUC único (transaccional). ⚠️ NO publica `ClientCreated` (solo `ImportClientsHandler` lo publica) | API + Web |
| C‑02 | `UpdateClient` | Actualiza datos básicos | API + Web |
| C‑03 | `ActivateClient` | Activa cliente INACTIVE → ACTIVE | API + Web |
| C‑04 | `DeactivateClient` | Desactiva cliente (cascada a terminales) | API + Web |
| C‑05 | `DeleteClient` | Soft delete cliente | API + Web |
| C‑06 | `AddContact` | Agrega contacto a cliente | API + Web |
| C‑07 | `UpdateContact` | Actualiza datos de contacto + publica `ContactUpdated` | API + Web |
| C‑08 | `DeactivateContact` | Desactiva contacto + publica `ContactDeactivated` (T31) | API + Web |
| C‑09 | `ReactivateContact` | Reactiva contacto + publica `ContactReactivated` (T31) | API + Web |
| C‑10 | `SoftDeleteContact` | Soft delete contacto | API + Web |
| C‑11 | `LinkUserToContact` | Vincula User de Identity a Contact | API + Web |
| C‑12 | `UnlinkUserFromContact` | Desvincula User + publica `ContactUserUnlinked` | API + Web |
| C‑13 | `ProvisionContactAccess` | Provisiona acceso a portal (crea/vincula User) + publica `ContactAccessProvisioned` | API + Web |
| C‑14 | `RegisterTerminal` | Registra terminal con serial único. ⚠️ NO publica `TerminalRegistered` | API + Web |
| C‑15 | `UpdateTerminal` | Actualiza terminal + publica `TerminalUpdated` | API + Web |
| C‑16 | `ActivateTerminal` | Activa terminal + publica `TerminalReactivated` | API + Web |
| C‑17 | `DeactivateTerminal` | Desactiva terminal + publica `TerminalDeactivated` | API + Web |
| C‑18 | `SoftDeleteTerminal` | Soft delete terminal | Web |
| C‑19 | `RegisterTerminalAssignment` | Asigna terminal a sitio + publica `TerminalAssignmentRegistered` | API + Web |
| C‑20 | `UpdateTerminalAssignment` | Actualiza asignación + publica `TerminalAssignmentUpdated` | API + Web |
| C‑21 | `ActivateTerminalAssignment` | Activa asignación + publica `TerminalAssignmentReactivated` | API + Web |
| C‑22 | `DeactivateTerminalAssignment` | Desactiva asignación + publica `TerminalAssignmentDeactivated` | API + Web |
| C‑23 | `RegisterWarranty` | Registra garantía + publica `WarrantyRegistered` | API + Web |
| C‑24 | `UpdateWarranty` | Actualiza garantía + publica `WarrantyUpdated` | API + Web |
| C‑25 | `CreateContract` | Crea contrato + publica `ContractCreated` | API + Web |
| C‑26 | `UpdateContract` | Actualiza contrato + publica `ContractUpdated` | API + Web |
| C‑27 | `CancelContract` | Cancela contrato + publica `ContractCancelled` | API + Web |
| C‑28 | `AddSite` | Agrega sitio a cliente | Web |
| C‑29 | `UpdateSite` | Actualiza sitio | Web |
| C‑30 | `DeactivateSite` | Desactiva sitio | Web |
| C‑31 | `ReactivateSite` | Reactiva sitio | Web |
| C‑32 | `ImportClients` (sync) | Importa clientes desde CSV, publica `ClientCreated` por fila, devuelve `ImportResult` | API + Web + CLI |
| C‑33 | `ImportClientsMessage` (async) | Wrapper async de `ImportClientsHandler` (≥100 filas) — registrado en `messenger.yaml` (async_events). ⚠️ Sin dispatcher activo (G‑12) | Messenger |
| C‑34 | `ImportTerminals` (sync) | Importa terminales desde CSV, devuelve `ImportResult` | API + Web + CLI |
| C‑35 | `ImportTerminalsMessage` (async) | Wrapper async de `ImportTerminalsHandler` — registrado en `messenger.yaml`. ⚠️ Sin dispatcher activo (G‑12) | Messenger |
| C‑36 | `ImportTerminalModels` | Importa modelos de terminal desde CSV (P2) | CLI |
| C‑37 | `ImportModelParts` | Importa partes de modelo desde CSV (P2) | CLI |

---

## 5. Queries

**Total real: 16** (14 con caller en producción + 1 solo en tests ⚠️ + dashboard).

| ID | Query | Handler | Response | Caller (ver §8) |
|----|-------|---------|----------|-----------------|
| Q‑01 | `ListClients` | Paginación + filtros (name, ruc, status) | `ClientResponse[]` | API + Web + **Support BC** (TicketController) |
| Q‑02 | `FindClientById` | Busca por ULID | `ClientResponse` | API + Web (Client + Site) |
| Q‑03 | `ListContactsByClient` | Lista contactos de un cliente | `ContactResponse[]` | ⚠️ **Sin caller en producción** — solo tests (G‑11) |
| Q‑04 | `FindContactById` | Busca contacto por ULID | `ContactResponse` | API + Web |
| Q‑05 | `FindContactsByClient` | Busca contactos por cliente | `ContactResponse[]` | API + Web |
| Q‑06 | `FindTerminalsByClient` | Lista terminales de un cliente | `TerminalResponse[]` | API + Web (Terminal + Assignment) |
| Q‑07 | `FindTerminalById` | Busca terminal por ULID | `TerminalResponse` | API + Web |
| Q‑08 | `ListTerminalAssignments` | Lista asignaciones | `TerminalAssignmentResponse[]` | API + Web |
| Q‑09 | `FindTerminalAssignmentById` | Busca asignación por ULID | `TerminalAssignmentResponse` | API + Web |
| Q‑10 | `ListSitesByClient` | Lista sitios de un cliente | `SiteResponse[]` | Web |
| Q‑11 | `FindSiteById` | Busca sitio por ULID | `SiteResponse` | Web |
| Q‑12 | `FindContractsByClient` | Lista contratos de un cliente | `ContractResponse[]` | API + Web |
| Q‑13 | `FindContractById` | Busca contrato por ULID | `ContractResponse` | API + Web |
| Q‑14 | `FindWarrantiesByTerminal` | Lista garantías de un terminal | `WarrantyResponse[]` | API + Web |
| Q‑15 | `FindWarrantyById` | Busca garantía por ULID | `WarrantyResponse` | API + Web |
| Q‑16 | `GetClientDashboardSummary` | 11 KPIs del dashboard de cliente (DBAL directo) | `ClientDashboardSummary` DTO | API + Web |

### KPIs del dashboard (Q‑16, verificado en `GetClientDashboardSummaryHandler`)

| KPI | Estado |
|-----|--------|
| Total clientes | ✅ |
| Contactos activos | ⚠️ bug: consulta tabla `contacts` (real: `client_contacts`) — G‑10 |
| Total terminales | ✅ |
| Terminales por estado | ✅ |
| Clientes recientes (5) | ✅ |
| Clientes por estado | ✅ |
| Contratos por vencer (30/60/90 días) | ✅ |
| Top clientes por terminales (10) | ✅ |
| Contactos por estado | ⚠️ bug: tabla `contacts` — G‑10 |
| Terminales sin asignación | ✅ |
| Crecimiento mensual (12 meses) | ✅ |

---

## 6. Domain Events

**Total definidos: 29.** Todos consumidos por `Notification/Infrastructure/Messenger/Handler/CrossBcDomainEventHandler.php` (vía `DomainEventMessage` async). **Solo 16 se publican realmente desde handlers** (ver columna "Publisher").

| Evento | Publisher real (handler) | Consumidores |
|--------|--------------------------|--------------|
| `ClientCreated` | `ImportClientsHandler` (por fila) ⚠️ `RegisterClientHandler` NO lo publica | Notification → CrossBcDomainEventHandler |
| `ClientUpdated` | ⚠️ sin publisher (C‑02 no lo publica) | Notification |
| `ClientDeactivated` | ⚠️ sin publisher (C‑04 no lo publica) | Notification |
| `ClientReactivated` | ⚠️ sin publisher (C‑03 no lo publica) | Notification |
| `ClientDeleted` | ⚠️ sin publisher (C‑05 no lo publica) | Notification |
| `ClientSaleOrderCreated` | ⚠️ **dead event** — sin publisher en todo el código (G‑09) | Notification |
| `ClientSaleOrderCompleted` | ⚠️ **dead event** — sin publisher en todo el código (G‑09) | Notification |
| `ContactAdded` | ⚠️ sin publisher (C‑06 no lo publica) | Notification |
| `ContactUpdated` | `UpdateContactHandler` | Notification |
| `ContactDeactivated` | `DeactivateContactHandler` (C‑08, T31) → **cascada Contact→User activa** | Notification + `ContactStatusCascadeHandler` |
| `ContactReactivated` | `ReactivateContactHandler` (C‑09, T31) → **cascada activa** | Notification + `ContactStatusCascadeHandler` |
| `ContactUserLinked` | `LinkUserToContactHandler` (C‑11, T31) | Notification |
| `ContactUserUnlinked` | `UnlinkUserFromContactHandler` | Notification |
| `ContactAccessProvisioned` | `ProvisionContactAccessHandler` | Notification |
| `TerminalRegistered` | ⚠️ sin publisher (C‑14 no lo publica) | Notification |
| `TerminalUpdated` | `UpdateTerminalHandler` | Notification |
| `TerminalDeactivated` | `DeactivateTerminalHandler` | Notification |
| `TerminalReactivated` | `ActivateTerminalHandler` | Notification |
| `TerminalAssignmentRegistered` | `RegisterTerminalAssignmentHandler` | Notification |
| `TerminalAssignmentUpdated` | `UpdateTerminalAssignmentHandler` | Notification |
| `TerminalAssignmentDeactivated` | `DeactivateTerminalAssignmentHandler` | Notification |
| `TerminalAssignmentReactivated` | `ActivateTerminalAssignmentHandler` | Notification |
| `ContractCreated` | `CreateContractHandler` | Notification |
| `ContractUpdated` | `UpdateContractHandler` | Notification |
| `ContractCancelled` | `CancelContractHandler` | Notification |
| `WarrantyRegistered` | `RegisterWarrantyHandler` | Notification |
| `WarrantyUpdated` | `UpdateWarrantyHandler` | Notification |
| `WarrantyExpired` | ⚠️ sin publisher — `CheckExpiringWarrantiesCommand` solo imprime (G‑08) | Notification |
| `SlaPolicyCreated` | ⚠️ **dead event** — sin publisher en todo el código (G‑09) | Notification |

**Subscriber intra-BC:** `ContactStatusCascadeHandler` (`Infrastructure/Messenger/Handler/`, `#[AsMessageHandler]` async) — cascada Contact → User (REQ-CST-002). Escucha `ContactDeactivated` / `ContactReactivated` (publicados por C‑08/C‑09, T31).

---

## 7. Cross-BC Interactions

| BC | Tipo | Interacción |
|----|------|-------------|
| **Identity** | Escritura | `LinkUserToContact` vincula un User de Identity a un Contact |
| **Identity** | Escritura | `ProvisionContactAccess` crea User a través de `InviteContactToPortal` (Identity BC) + publica `ContactAccessProvisioned` |
| **Identity** | Lectura | `ContactStatusCascadeHandler` desactiva/reactiva User cuando Contact cambia (⚠️ inerte — G‑04) |
| **Identity** | Lectura | `UnlinkUserFromContactHandler` desactiva el User vinculado |
| **Notification** | Evento | `CrossBcDomainEventHandler` consume los 29 Domain Events del BC (notificaciones fire-and-forget) |
| **Location** | Lectura | `cityId` en Site referencia Location BC |
| **Organization** | Lectura | `serviceZoneId` en Site referencia Organization BC |
| **Support** | Lectura | `ListClientsQuery` consumida por `Support/Presentation/Http/TicketController.php` (selector de cliente en tickets) |
| **Support** | Lectura | Terminal models y asignaciones referenciadas desde tickets |

---

## 8. Wiring (verificado 2026-08-07)

> Verificación: `php bin/wiring-check.php` → **346 targets, 0 huérfanos** (exit 0). Callers confirmados con grep + CodeGraph sobre `src/` y `config/`. Excepciones detectadas: G‑11 (ListContactsByClient, falso negativo del script) y dead events (G‑08/G‑09).

### 8.1 Commands → Callers

| Command | Caller real (archivo) |
|---------|------------------------|
| `RegisterClient` | `Presentation/Http/ApiClientController.php::create`, `ClientController.php::create` |
| `UpdateClient` | `ApiClientController.php::update`, `ClientController.php::edit` |
| `ActivateClient` | `ApiClientController.php::reactivate`, `ClientController.php::activate` |
| `DeactivateClient` | `ApiClientController.php::deactivate`, `ClientController.php::deactivate` |
| `DeleteClient` | `ApiClientController.php::delete`, `ClientController.php::delete` |
| `AddContact` | `ApiClientContactController.php::create`, `ContactController.php::create` |
| `UpdateContact` | `ApiClientContactController.php::update`, `ContactController.php` (update) |
| `DeactivateContact` | `ApiClientContactController.php::deactivate`, `ContactController.php::deactivate` |
| `ReactivateContact` | `ApiClientContactController.php::reactivate`, `ContactController.php::reactivate` |
| `SoftDeleteContact` | `ApiClientContactController.php::delete`, `ContactController.php::delete` |
| `LinkUserToContact` | `ApiClientContactController.php::linkUser`, `ContactController.php::linkUser` |
| `UnlinkUserFromContact` | `ApiClientContactController.php::unlinkUser`, `ContactController.php::unlinkUser` |
| `ProvisionContactAccess` | `ApiClientContactController.php::provisionAccess`, `ContactController.php::createUser` |
| `RegisterTerminal` | `ApiClientTerminalController.php::create`, `TerminalController.php::create` |
| `UpdateTerminal` | `ApiClientTerminalController.php::update`, `TerminalController.php::edit` |
| `ActivateTerminal` | `ApiClientTerminalController.php::reactivate`, `TerminalController.php::activate` |
| `DeactivateTerminal` | `ApiClientTerminalController.php::deactivate`, `TerminalController.php::deactivate` |
| `SoftDeleteTerminal` | `TerminalController.php::delete` (web only) |
| `RegisterTerminalAssignment` | `ApiClientTerminalAssignmentController.php::create`, `TerminalAssignmentController.php::create` |
| `UpdateTerminalAssignment` | `ApiClientTerminalAssignmentController.php::update`, `TerminalAssignmentController.php::edit` |
| `ActivateTerminalAssignment` | `ApiClientTerminalAssignmentController.php::reactivate`, `TerminalAssignmentController.php::reactivate` |
| `DeactivateTerminalAssignment` | `ApiClientTerminalAssignmentController.php::deactivate`, `TerminalAssignmentController.php::deactivate` |
| `RegisterWarranty` | `ApiClientTerminalWarrantyController.php::create`, `TerminalWarrantyController.php::create` |
| `UpdateWarranty` | `ApiClientTerminalWarrantyController.php::update`, `TerminalWarrantyController.php::update` |
| `CreateContract` | `ApiClientContractController.php::create`, `ContractController.php::create` |
| `UpdateContract` | `ApiClientContractController.php::update`, `ContractController.php::update` |
| `CancelContract` | `ApiClientContractController.php::cancel`, `ContractController.php::cancel` |
| `AddSite` | `SiteController.php::add` (web) |
| `UpdateSite` | `SiteController.php::edit` (web) |
| `DeactivateSite` | `SiteController.php::deactivate` (web) |
| `ReactivateSite` | `SiteController.php::reactivate` (web) |
| `ImportClients` | `ApiClientImportController.php::importClients`, `ClientImportController.php::importClients`, CLI `client:import:clients` |
| `ImportClientsMessage` | `config/packages/messenger.yaml` (routing async_events) ⚠️ sin dispatcher (G‑12) |
| `ImportTerminals` | `ApiClientImportController.php::importTerminals`, `ClientImportController.php::importTerminals`, CLI `client:import:terminals` |
| `ImportTerminalsMessage` | `config/packages/messenger.yaml` (routing async_events) ⚠️ sin dispatcher (G‑12) |
| `ImportTerminalModels` | CLI `client:import:terminal-models` (`ClientImportTerminalModelsCliCommand`) |
| `ImportModelParts` | CLI `client:import:model-parts` (`ClientImportModelPartsCliCommand`) |

### 8.2 Queries → Callers

| Query | Caller real (archivo) |
|-------|------------------------|
| `ListClients` | `ApiClientController.php::list`, `ClientController.php::list`, **`src/app/Support/Presentation/Http/TicketController.php`** (cross-BC) |
| `FindClientById` | `ApiClientController.php::show`, `ClientController.php::show`, `SiteController.php` |
| `ListContactsByClient` | ⚠️ **Ninguno en producción** (solo `tests/Unit/Client/Application/Query/ListContactsByClientHandlerTest.php`) — G‑11 |
| `FindContactById` | `ApiClientContactController.php::show`, `ContactController.php` |
| `FindContactsByClient` | `ApiClientContactController.php::list`, `ClientController.php::show` |
| `FindTerminalsByClient` | `ApiClientTerminalController.php::list`, `TerminalController.php::list`, `TerminalAssignmentController.php` |
| `FindTerminalById` | `ApiClientTerminalController.php::show`, `TerminalController.php::show` |
| `ListTerminalAssignments` | `ApiClientTerminalAssignmentController.php::list`, `TerminalAssignmentController.php::list` |
| `FindTerminalAssignmentById` | `ApiClientTerminalAssignmentController.php::show`, `TerminalAssignmentController.php::show` |
| `ListSitesByClient` | `ClientController.php::sites` |
| `FindSiteById` | `SiteController.php::show` |
| `FindContractsByClient` | `ApiClientContractController.php::list`, `ContractController.php::list` |
| `FindContractById` | `ApiClientContractController.php::show`, `ContractController.php::show` |
| `FindWarrantiesByTerminal` | `ApiClientTerminalWarrantyController.php::list`, `TerminalWarrantyController.php::list` |
| `FindWarrantyById` | `ApiClientTerminalWarrantyController.php::show`, `TerminalWarrantyController.php::show` |
| `GetClientDashboardSummary` | `ApiClientDashboardController.php::summary`, `ClientDashboardController.php::index` |

### 8.3 Eventos / Subscribers → Callers

| Evento/Subscriber | Caller real |
|-------------------|-------------|
| 29 Domain Events | Publisher: handlers de §6 (16 reales). Consumidor: `Notification/.../CrossBcDomainEventHandler.php` (routing async_events en `messenger.yaml`) |
| `ContactStatusCascadeHandler` | `#[AsMessageHandler]` (Messenger) — escucha `ContactDeactivated`/`ContactReactivated` (publicados por C‑08/C‑09, T31) |

### 8.4 Reportes → Callers

| Reporte | Caller real |
|---------|-------------|
| `ContractsExpiringReportGenerator` | `ClientReportController.php::contractsExpiring` — `GET /client/report/contracts-expiring.{xlsx\|pdf}` |
| `TerminalsByClientReportGenerator` | `ClientReportController.php::terminalsByClient` — `GET /client/report/terminals-by-client.{xlsx\|pdf}` |

### 8.5 Console → Registro

| Comando CLI | Caller (registro) |
|-------------|-------------------|
| `client:check-expiring-warranties` | `#[AsCommand]` + `config/services.yaml` (console.command) |
| `client:import:clients` | `#[AsCommand]` (CLI) |
| `client:import:terminals` | `#[AsCommand]` (CLI) |
| `client:import:terminal-models` | `#[AsCommand]` (CLI) |
| `client:import:model-parts` | `#[AsCommand]` (CLI) |

---

## 9. API Endpoints (verificados con `debug:router`, 2026-08-07)

### API REST (`/api/v1/client/`) — 43 rutas

| Método | Ruta | Permiso |
|--------|------|---------|
| `GET` | `/clients` | `client.view` |
| `POST` | `/clients` | `client.create` |
| `GET` | `/clients/{id}` | `client.view` |
| `PATCH` | `/clients/{id}` | `client.update` |
| `POST` | `/clients/{id}/deactivate` | `client.update` |
| `POST` | `/clients/{id}/reactivate` | `client.update` |
| `DELETE` | `/clients/{id}` | `client.delete` |
| `GET` | `/clients/{clientId}/contacts` | `contact.view` |
| `POST` | `/clients/{clientId}/contacts` | `contact.create` |
| `GET` | `/clients/{clientId}/contacts/{id}` | `contact.view` |
| `PATCH` | `/clients/{clientId}/contacts/{id}` | `contact.update` |
| `POST` | `/clients/{clientId}/contacts/{id}/link-user` | `contact.update` |
| `POST` | `/clients/{clientId}/contacts/{id}/unlink-user` | `contact.update` |
| `POST` | `/clients/{clientId}/contacts/{id}/provision-access` | `contact.update` |
| `POST` | `/clients/{clientId}/contacts/{id}/deactivate` | `contact.update` |
| `POST` | `/clients/{clientId}/contacts/{id}/reactivate` | `contact.update` |
| `DELETE` | `/clients/{clientId}/contacts/{id}` | `contact.delete` |
| `GET` | `/clients/{clientId}/terminals` | `client.view` |
| `POST` | `/clients/{clientId}/terminals` | `client.update` |
| `GET` | `/clients/{clientId}/terminals/{id}` | `client.view` |
| `PATCH` | `/clients/{clientId}/terminals/{id}` | `client.update` |
| `POST` | `/clients/{clientId}/terminals/{id}/deactivate` | `client.update` |
| `POST` | `/clients/{clientId}/terminals/{id}/reactivate` | `client.update` |
| `GET` | `/clients/{clientId}/contracts` | `client.view` |
| `POST` | `/clients/{clientId}/contracts` | `client.update` |
| `GET` | `/clients/{clientId}/contracts/{id}` | `client.view` |
| `PATCH` | `/clients/{clientId}/contracts/{id}` | `client.update` |
| `POST` | `/clients/{clientId}/contracts/{id}/cancel` | `client.update` |
| `GET` | `/terminal-assignments` | `client.view` |
| `POST` | `/terminal-assignments` | `client.update` |
| `GET` | `/terminal-assignments/{id}` | `client.view` |
| `PATCH` | `/terminal-assignments/{id}` | `client.update` |
| `POST` | `/terminal-assignments/{id}/deactivate` | `client.update` |
| `POST` | `/terminal-assignments/{id}/reactivate` | `client.update` |
| `GET` | `/clients/{clientId}/terminals/{terminalId}/warranties` | `client.view` |
| `POST` | `/clients/{clientId}/terminals/{terminalId}/warranties` | `client.update` |
| `GET` | `/clients/{clientId}/terminals/{terminalId}/warranties/{id}` | `client.view` |
| `PATCH` | `/clients/{clientId}/terminals/{terminalId}/warranties/{id}` | `client.update` |
| `GET` | `/dashboard/summary` | `client.view` |
| `POST` | `/import/clients` | `client.import` |
| `GET` | `/import/clients/template` | Público (sin `IsGranted`) |
| `POST` | `/import/terminals` | `client.import` |
| `GET` | `/import/terminals/template` | Público (sin `IsGranted`) |

> Nota: la tabla anterior es la lista **real** de `debug:router`. El spec anterior listaba rutas que no existen (ej. `/terminal-assignments/{id}` con `client.view` correcto pero faltaban link-user/unlink-user/provision-access/reactivate) y verbos `PUT` — la realidad usa `PATCH`.

### Web Routes (47 rutas, controllers `src/app/Client/Presentation/Http/`)

| Ruta | Controller | Permiso |
|------|------------|---------|
| `/clients` | `ClientController::list` | `client.view_all` |
| `/clients/new` | `ClientController::new` | `client.create` |
| `/clients` (POST) | `ClientController::create` | `client.create` |
| `/clients/{id}` | `ClientController::show` | `client.view` |
| `/clients/{id}/edit` (POST) | `ClientController::edit` | `client.update` |
| `/clients/{id}/activate` (POST) | `ClientController::activate` | `client.activate` |
| `/clients/{id}/deactivate` (POST) | `ClientController::deactivate` | `client.deactivate` |
| `/clients/{id}/delete` (POST) | `ClientController::delete` | `client.delete` |
| `/client/dashboard` | `ClientDashboardController::index` | `client.view` |
| `/client/import` | `ClientImportController::index` | `client.import` |
| `/client/import/clients` (POST) | `ClientImportController::importClients` | `client.import` |
| `/client/import/terminals` (POST) | `ClientImportController::importTerminals` | `client.import` |
| `/client/report/contracts-expiring.{_format}` | `ClientReportController::contractsExpiring` | `client.report` |
| `/client/report/terminals-by-client.{_format}` | `ClientReportController::terminalsByClient` | `client.report` |
| `/clients/{clientId}/create` (POST) | `ContactController::create` | `contact.create` |
| `/clients/{clientId}/{contactId}/create-user` (POST) | `ContactController::createUser` (provision) | `contact.update` |
| `/clients/{clientId}/{contactId}/link-user` (POST) | `ContactController::linkUser` | `contact.update` |
| `/clients/{clientId}/{contactId}/deactivate` (POST) | `ContactController::deactivate` | `contact.update` |
| `/clients/{clientId}/{contactId}/reactivate` (POST) | `ContactController::reactivate` | `contact.update` |
| `/clients/{clientId}/{contactId}/delete` (POST) | `ContactController::delete` | `contact.delete` |
| `/clients/{clientId}/contracts` (GET/POST) | `ContractController` | `client.view` / `client.update` |
| `/clients/{clientId}/contracts/new` | `ContractController::new` | `client.view` |
| `/clients/{clientId}/contracts/{id}` (GET) | `ContractController::show` | `client.view` |
| `/clients/{clientId}/contracts/{id}/cancel` (POST) | `ContractController::cancel` | `client.update` |
| `/clients/{clientId}/sites` (POST) | `SiteController::add` | `client.update` |
| `/clients/{clientId}/sites/{siteId}` (GET) | `SiteController::show` | `client.view` |
| `/clients/{clientId}/sites/{siteId}/edit` (POST) | `SiteController::edit` | `client.update` |
| `/clients/{clientId}/sites/{siteId}/deactivate` (POST) | `SiteController::deactivate` | `client.update` |
| `/clients/{clientId}/sites/{siteId}/reactivate` (POST) | `SiteController::reactivate` | `client.update` |
| `/clients/{clientId}/terminal-assignments` (GET/POST) | `TerminalAssignmentController` | `client.view` / `client.update` |
| `/clients/{clientId}/terminal-assignments/new` | `TerminalAssignmentController::new` | `client.view` |
| `/clients/{clientId}/terminal-assignments/{id}` (GET) | `TerminalAssignmentController::show` | `client.view` |
| `/clients/{clientId}/terminal-assignments/{id}/deactivate|reactivate` (POST) | `TerminalAssignmentController` | `client.update` |
| `/clients/{clientId}/terminals` (GET/POST) | `TerminalController` | `client.view` / `client.update` |
| `/clients/{clientId}/terminals/new` | `TerminalController::new` | `client.view` |
| `/clients/{clientId}/terminals/{id}` (GET) | `TerminalController::show` | `client.view` |
| `/clients/{clientId}/terminals/{id}/delete|deactivate|reactivate` (POST) | `TerminalController` | `client.update` |
| `/clients/{clientId}/terminals/{terminalId}/warranties` (GET/POST) | `TerminalWarrantyController` | `client.view` / `client.update` |
| `/clients/{clientId}/terminals/{terminalId}/warranties/new` | `TerminalWarrantyController::new` | `client.view` |
| `/clients/{clientId}/terminals/{terminalId}/warranties/{id}` (GET) | `TerminalWarrantyController::show` | `client.view` |

---

## 10. Console Commands (verificados — 5 reales)

| Comando | Clase | Propósito |
|---------|-------|-----------|
| `client:check-expiring-warranties` | `CheckExpiringWarrantiesCommand` | Verifica warranties próximas a vencer (30 días) e imprime alertas. ⚠️ No publica `WarrantyExpired` (G‑08) |
| `client:import:clients` | `ClientImportClientsCliCommand` | Importa clientes desde CSV |
| `client:import:terminals` | `ClientImportTerminalsCliCommand` | Importa terminales desde CSV |
| `client:import:terminal-models` | `ClientImportTerminalModelsCliCommand` | Importa modelos de terminal desde CSV (P2) |
| `client:import:model-parts` | `ClientImportModelPartsCliCommand` | Importa partes de modelo desde CSV (P2) |

---

## 11. Known Gaps / Próximos Pasos (estado REAL 2026-08-07)

### Resueltos desde la última versión del spec

| # | Gap anterior | Estado |
|---|--------------|--------|
| G‑01 | TerminalModel y TerminalWarranty sin ORM mapping | ✅ **Resuelto** — 8 mappings creados (`ModelPart.orm.xml` incluido), tablas `client_*` |
| G‑02 | Dashboard KPIs incompletos | ✅ **Resuelto** — 11 KPIs implementados en `GetClientDashboardSummaryHandler` (DBAL) |
| G‑03 | Importers sin UI ni template | ✅ **Resuelto** — endpoints `/import/{clients\|terminals}/template` + UI web `/client/import` |
| G‑05 | Terminal.orm.xml usaba `type="string"` + `enumType` | ✅ **Resuelto** — 28 custom DBAL types (`terminal_vendor`, `terminal_functionality`, `terminal_type`, `terminal_status`, `atm_model_code`, `model_part_id`, etc.) |

### Gaps vigentes

| # | Gap | Prioridad | Detalle |
|---|-----|-----------|---------|
| G‑04 | **Publicación inconsistente de Domain Events** | 🟡 Media | **Cascada Contact→User ✅ resuelta (T31)** — `DeactivateContact`/`ReactivateContact`/`LinkUserToContact` publican sus eventos (CL‑05/CL‑10 operativas). Pendiente (19/29 publicados): `RegisterClient`, `UpdateClient`, `Activate/DeactivateClient`, `DeleteClient`, `AddContact`, `RegisterTerminal` aún no publican |
| G‑08 | **`WarrantyExpired` sin publisher** | 🟡 Media | `CheckExpiringWarrantiesCommand` detecta garantías próximas a vencer pero solo imprime; el evento `WarrantyExpired` (consumido por Notification) nunca se publica |
| G‑09 | **Dead events** | 🟡 Media | `ClientSaleOrderCreated`, `ClientSaleOrderCompleted`, `SlaPolicyCreated` definidos y consumidos por Notification pero sin ningún publisher en todo el código |
| G‑10 | **Bug SQL en dashboard (KPIs de contactos)** | 🟡 Media | `GetClientDashboardSummaryHandler::countActiveContacts()` y `contactsByStatus()` consultan tabla `contacts`; el mapping real es `client_contacts` → error/resultado incorrecto en runtime |
| G‑11 | **`ListContactsByClientQuery` sin caller** | 🟡 Media | Query sin uso en producción (solo tests). Los controllers usan `FindContactsByClient`. **Falso negativo del `bin/wiring-check.php`**: la exclusión de "handler pareja" (`str_contains(basename($otherFile), $basename)`, línea 128) falla porque `ListContactsByClientHandler.php` no contiene la subcadena `ListContactsByClientQuery.php`; el handler importa el FQCN del Query (línea 143) y el script lo cuenta como caller. Punto ciego estructural: aplica a todo Query con handler pareja que lo importe. Candidata a borrar o conectar |
| G‑12 | **Importers async sin dispatcher** | 🟢 Baja | `ImportClientsMessage`/`ImportTerminalsMessage` registrados en `messenger.yaml` (async_events) con su `MessageHandler`, pero ningún código los despacha (los controllers y CLI usan el handler sync directo) |
| G‑13 | **UI de modelos/partes solo CLI** | 🟢 Baja | `ImportTerminalModels` e `ImportModelParts` (P2) solo tienen CLI; sin endpoint API/web ni template descargable (a diferencia de clientes/terminales) |
| G‑06 | **Cobertura de tests** | 🟡 Media | Real: **84 archivos** (79 Unit + 5 Functional) — ya no "11+1". Faltan tests para: `RegisterClientHandler`, `UpdateClientHandler`, `ActivateClientHandler`, `DeactivateClientHandler`, `DeleteClientHandler`, `AddContactHandler`, `ReactivateContactHandler`, `DeactivateContactHandler`, `RegisterTerminalHandler`, `ActivateTerminalAssignmentHandler`, `DeactivateTerminalAssignmentHandler`, `ImportModelPartsHandler` y handlers de sitios (parcial) |

---

## 12. Estado real por capa (resumen verificado)

| Capa | Componente | Conteo real | Estado |
|------|-----------|-------------|--------|
| **Domain** | Entities | 9 (Client, Contact, Terminal, TerminalAssignment, Site, Contract, TerminalModel, TerminalWarranty, ModelPart) | ✅ |
| **Domain** | ValueObjects | 17 | ✅ |
| **Domain** | Enums | 10 | ✅ |
| **Domain** | Domain Events | 29 definidos (19 publicados) | ⚠️ G‑04 (parcial)/G‑08/G‑09 |
| **Domain** | Repository interfaces | 8 | ✅ |
| **Domain** | Exceptions | 22 | ✅ |
| **Application** | Commands | 37 (35 sync + 2 async) | ✅ |
| **Application** | Queries | 16 | ⚠️ G‑11 |
| **Application** | Response DTOs | 7 | ✅ |
| **Application** | Reportes | 2 (ContractsExpiring, TerminalsByClient) | ✅ |
| **Application** | Exceptions | 9 | ✅ |
| **Infrastructure** | Doctrine repos | 8 | ✅ |
| **Infrastructure** | ORM mappings | 8 | ✅ |
| **Infrastructure** | DBAL types | 28 | ✅ |
| **Infrastructure** | Import parsers/validators | 6 (Client, Terminal, TerminalModel, ModelPart + 2 validators) | ✅ |
| **Infrastructure** | Messenger handlers | 1 (ContactStatusCascadeHandler) | ⚠️ G‑04 |
| **Infrastructure** | Console commands | 5 | ✅ |
| **Presentation** | API controllers | 8 (43 rutas) | ✅ |
| **Presentation** | Web controllers | 10 (47 rutas) | ✅ |
| **Tests** | Unit + Functional | 84 (79 + 5) | ⚠️ G‑06 |
