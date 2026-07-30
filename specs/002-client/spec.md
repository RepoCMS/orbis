# Feature Specification: Client Management

**Feature Branch**: `002-client`

**Created**: 2026-06-17

**Status**: In Development (~70% implemented)

---

## 1. Descripción General

El Bounded Context **Client** gestiona todo lo relacionado con los clientes de ACMS, sus terminales/cajeros automáticos, contactos, contratos, sitios de instalación, warranties y asignaciones. Es el BC central que define la relación contractual con los bancos y otras instituciones financieras.

Responsabilidades:
- Registro y ciclo de vida de clientes (personas jurídicas)
- Gestión de contactos por cliente
- Vinculación de contactos con usuarios del sistema (Identity BC)
- Registro y ciclo de vida de terminales (cajeros automáticos)
- Asignación de terminales a sitios de clientes
- Contratos de servicio con SLAs
- Garantías (warranties) de terminales
- Dashboard de KPIs por cliente
- Importación masiva de clientes y terminales
- Catálogo de modelos de terminal

---

## 2. Business Rules

| # | Regla | Descripción |
|---|-------|-------------|
| CL‑01 | **RUC único** | No pueden existir dos clientes con el mismo RUC. |
| CL‑02 | **Contacto obligatorio** | Todo cliente debe tener al menos 1 contacto activo (excepto en cascada de desactivación). |
| CL‑03 | **Serial único** | El número de serie de un terminal debe ser único en el sistema. |
| CL‑04 | **Ciclo de vida cliente** | ACTIVE → INACTIVE. Desactivar un cliente inactiva todos sus terminales, contactos, sitios y contratos. |
| CL‑05 | **Vinculación contacto-usuario** | Un contacto puede vincularse a un User de Identity BC para acceso al portal. La desvinculación desactiva el User. |
| CL‑06 | **Asignación de terminal** | Un terminal puede asignarse a un sitio de un cliente. Una asignación puede ser OPERATIVE o INOPERATIVE. |
| CL‑07 | **Warranty** | Una garantía puede ser de fabricante (manufacturer) o extendida. Se verifica expiración periódicamente. |
| CL‑08 | **Importación masiva** | Clientes y terminales pueden importarse vía CSV. Por cada fila importada se publica un Domain Event. |
| CL‑09 | **Contrato con SLA** | Los contratos definen el alcance del servicio y pueden incluir SLAs (evaluados por Support BC). |
| CL‑10 | **Cascada desactivación** | Desactivar un cliente → contactos INACTIVE → users de Identity desactivados. Reactivar revierte la cascada. |

---

## 3. Aggregates

### 3.1 Client (Aggregate Root)

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

### 3.2 Contact

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

### 3.3 Terminal

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `TerminalIdValueObject` (ULID) | Identificador único |
| `clientId` | `ClientIdValueObject` | Cliente propietario |
| `serial` | `SerialNumberValueObject` | Número de serie único |
| `vendor` | `TerminalVendor` | hyosung / diebold / ncr / grg |
| `funcionality` | `TerminalFunctionality` | dispenser / recycler / multinfunction / counter |
| `type` | `TerminalType` | lobby / ttw |
| `status` | `TerminalStatus` | ACTIVE / INACTIVE |
| `deletedAt` | `?DateTimeImmutable` | Soft delete |

**Factory:** `Terminal::add(id, clientId, serial, vendor, funcionality, type): self`

### 3.4 TerminalAssignment

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `TerminalAssignmentIdValueObject` (ULID) | Identificador único |
| `terminalId` | `TerminalIdValueObject` | Terminal asignado |
| `name` | `TerminalNameValueObject` | Nombre de la asignación |
| `idAssignedByClient` | `TerminalIdAssignedByClientValueObject` | ID del cliente (max 25) |
| `siteId` | `SiteIdValueObject` | Sitio de instalación |
| `status` | `TerminalAssignmentStatus` | OPERATIVE / INOPERATIVE |
| `deletedAt` | `?DateTimeImmutable` | Soft delete |

### 3.5 Site

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

### 3.6 Contract

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `ContractIdValueObject` (ULID) | Identificador único |
| `clientId` | `ClientIdValueObject` | Cliente |
| `startDate` | `DateTimeImmutable` | Fecha inicio |
| `endDate` | `?DateTimeImmutable` | Fecha fin (null = indefinido) |
| `terms` | `?string` | Términos |
| `scope` | `?string` | Alcance |
| `status` | `ContractStatus` | ACTIVE / EXPIRED / CANCELLED |

### 3.7 TerminalModel

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `TerminalModelIdValueObject` (ULID) | Identificador único |
| `name` | `TerminalModelNameValueObject` | Nombre del modelo |
| `description` | `?string` | Descripción |
| `createdAt` | `DateTimeImmutable` | Fecha creación |

### 3.8 TerminalWarranty

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | `TerminalWarrantyIdValueObject` (ULID) | Identificador único |
| `terminalId` | `TerminalIdValueObject` | Terminal |
| `type` | `WarrantyType` | manufacturer / extended |
| `startDate` | `DateTimeImmutable` | Inicio |
| `endDate` | `DateTimeImmutable` | Fin |
| `terms` | `?string` | Términos |

---

## 4. Commands

| ID | Command | Handler | Disparado desde |
|----|---------|---------|-----------------|
| C‑01 | `RegisterClient` | Crea cliente + publica `ClientCreated` | API + Web |
| C‑02 | `UpdateClient` | Actualiza datos básicos | API + Web |
| C‑03 | `ActivateClient` | Activa cliente INACTIVE → ACTIVE | API + Web |
| C‑04 | `DeactivateClient` | Desactiva cliente + cascada contactos/terminales | API + Web |
| C‑05 | `DeleteClient` | Soft delete cliente | API + Web |
| C‑06 | `AddContact` | Agrega contacto a cliente | API + Web |
| C‑07 | `UpdateContact` | Actualiza datos de contacto | API + Web |
| C‑08 | `DeactivateContact` | Desactiva contacto + cascada a User vinculado | API + Web |
| C‑09 | `ReactivateContact` | Reactiva contacto | API + Web |
| C‑10 | `SoftDeleteContact` | Soft delete contacto | API + Web |
| C‑11 | `LinkUserToContact` | Vincula User de Identity a Contact | API + Web |
| C‑12 | `UnlinkUserFromContact` | Desvincula User | API + Web |
| C‑13 | `ProvisionContactAccess` | Provisiona acceso a portal para contacto | API + Web |
| C‑14 | `RegisterTerminal` | Registra terminal con serial único | API + Web |
| C‑15 | `UpdateTerminal` | Actualiza terminal | API + Web |
| C‑16 | `ActivateTerminal` | Activa terminal | API + Web |
| C‑17 | `DeactivateTerminal` | Desactiva terminal | API + Web |
| C‑18 | `SoftDeleteTerminal` | Soft delete terminal | API + Web |
| C‑19 | `RegisterTerminalAssignment` | Asigna terminal a sitio | API + Web |
| C‑20 | `UpdateTerminalAssignment` | Actualiza asignación | API + Web |
| C‑21 | `ActivateTerminalAssignment` | Activa asignación | API + Web |
| C‑22 | `DeactivateTerminalAssignment` | Desactiva asignación | API + Web |
| C‑23 | `RegisterWarranty` | Registra garantía para terminal | API + Web |
| C‑24 | `UpdateWarranty` | Actualiza garantía | API + Web |
| C‑25 | `CreateContract` | Crea contrato para cliente | API + Web |
| C‑26 | `UpdateContract` | Actualiza contrato | API + Web |
| C‑27 | `CancelContract` | Cancela contrato | API + Web |
| C‑28 | `AddSite` | Agrega sitio a cliente | Web |
| C‑29 | `UpdateSite` | Actualiza sitio | Web |
| C‑30 | `DeactivateSite` | Desactiva sitio | Web |
| C‑31 | `ReactivateSite` | Reactiva sitio | Web |
| C‑32 | `ImportClients` (sync) + `ImportClientsMessage` (async) | Importa clientes desde CSV | API + Web + Console |
| C‑33 | `ImportTerminals` (sync) + `ImportTerminalsMessage` (async) | Importa terminales desde CSV | API + Web + Console |

## 5. Queries

| ID | Query | Handler | Response |
|----|-------|---------|----------|
| Q‑01 | `ListClients` | Paginación + filtros (name, ruc, status) | `ClientResponse[]` |
| Q‑02 | `FindClientById` | Busca por ULID | `ClientResponse` |
| Q‑03 | `ListContactsByClient` | Lista contactos de un cliente | `ContactResponse[]` |
| Q‑04 | `FindContactById` | Busca contacto por ULID | `ContactResponse` |
| Q‑05 | `FindContactsByClient` | Busca contactos por cliente | `ContactResponse[]` |
| Q‑06 | `ListTerminalsByClient` | Lista terminales de un cliente | `TerminalResponse[]` |
| Q‑07 | `FindTerminalById` | Busca terminal por ULID | `TerminalResponse` |
| Q‑08 | `ListTerminalAssignments` | Lista asignaciones | `TerminalAssignmentResponse[]` |
| Q‑09 | `FindTerminalAssignmentById` | Busca asignación por ULID | `TerminalAssignmentResponse` |
| Q‑10 | `ListSitesByClient` | Lista sitios de un cliente | `SiteResponse[]` |
| Q‑11 | `FindSiteById` | Busca sitio por ULID | `SiteResponse` |
| Q‑12 | `ListContractsByClient` | Lista contratos de un cliente | `ContractResponse[]` |
| Q‑13 | `FindContractById` | Busca contrato por ULID | `ContractResponse` |
| Q‑14 | `ListWarrantiesByTerminal` | Lista garantías de un terminal | `WarrantyResponse[]` |
| Q‑15 | `FindWarrantyById` | Busca garantía por ULID | `WarrantyResponse` |
| Q‑16 | `GetClientDashboardSummary` | KPIs del dashboard de cliente | `ClientDashboardSummary` DTO |

---

## 6. Domain Events

| Evento | Disparado por | Suscriptores |
|--------|---------------|-------------|
| `ClientCreated` | C‑01 RegisterClient | — |
| `ClientUpdated` | C‑02 UpdateClient | — |
| `ClientDeactivated` | C‑04 DeactivateClient | ContactStatusCascadeHandler |
| `ClientReactivated` | C‑03 ActivateClient | ContactStatusCascadeHandler |
| `ClientDeleted` | C‑05 DeleteClient | — |
| `ContactAdded` | C‑06 AddContact | — |
| `ContactUpdated` | C‑07 UpdateContact | — |
| `ContactDeactivated` | C‑08 DeactivateContact | ContactStatusCascadeHandler |
| `ContactReactivated` | C‑09 ReactivateContact | ContactStatusCascadeHandler |
| `ContactUserLinked` | C‑11 LinkUserToContact | — |
| `ContactUserUnlinked` | C‑12 UnlinkUserFromContact | — |
| `TerminalRegistered` | C‑14 RegisterTerminal | — |
| `TerminalUpdated` | C‑15 UpdateTerminal | — |
| `TerminalDeactivated` | C‑17 DeactivateTerminal | — |
| `TerminalReactivated` | C‑16 ActivateTerminal | — |
| `TerminalAssignmentRegistered` | C‑19 RegisterTerminalAssignment | — |
| `TerminalAssignmentUpdated` | C‑20 UpdateTerminalAssignment | — |
| `TerminalAssignmentDeactivated` | C‑22 DeactivateTerminalAssignment | — |
| `TerminalAssignmentReactivated` | C‑21 ActivateTerminalAssignment | — |
| `ContractCreated` | C‑25 CreateContract | — |
| `ContractUpdated` | C‑26 UpdateContract | — |
| `ContractCancelled` | C‑27 CancelContract | — |
| `WarrantyRegistered` | C‑23 RegisterWarranty | — |
| `WarrantyUpdated` | C‑24 UpdateWarranty | — |
| `WarrantyExpired` | CheckExpiringWarrantiesCommand | — |
| `SlaPolicyCreated` | (desde contrato con SLA) | — |

---

## 7. Cross-BC Interactions

| BC | Tipo | Interacción |
|----|------|-------------|
| **Identity** | Escritura | `LinkUserToContact` vincula un User de Identity a un Contact |
| **Identity** | Escritura | `ProvisionContactAccess` crea User a través de `InviteContactToPortal` |
| **Identity** | Lectura | `ContactStatusCascadeHandler` desactiva/reactiva User cuando Contact cambia |
| **Location** | Lectura | `cityId` en Site referencia Location BC |
| **Organization** | Lectura | `serviceZoneId` en Site referencia Organization BC |
| **Support** | Lectura | Terminal models y asignaciones referenciadas desde tickets |

---

## 8. Wiring

| Command/Query | Caller real |
|---------------|-------------|
| `RegisterClient` | `ApiClientController::create()`, `ClientController::create()` |
| `UpdateClient` | `ApiClientController::update()`, `ClientController::edit()` |
| `ActivateClient` | `ApiClientController::activate()` |
| `DeactivateClient` | `ApiClientController::deactivate()`, `ClientController::deactivate()` |
| `DeleteClient` | `ApiClientController::delete()`, `ClientController::delete()` |
| `AddContact` | `ApiClientContactController::create()`, `ContactController::create()` |
| `RegisterTerminal` | `ApiClientTerminalController::create()`, `TerminalController::create()` |
| `ImportClients` | `ApiClientImportController::importClients()`, `ClientImportController` |
| `ImportTerminals` | `ApiClientImportController::importTerminals()`, `ClientImportController` |
| `GetClientDashboardSummary` | `ApiClientDashboardController::summary()`, `ClientDashboardController` |
| `ListClients` | `ApiClientController::list()`, `ClientController::list()` |

---

## 9. API Endpoints

### API REST (`/api/v1/client/`)

| Método | Ruta | Permiso |
|--------|------|---------|
| `GET` | `/clients` | `client.view` |
| `POST` | `/clients` | `client.create` |
| `GET` | `/clients/{id}` | `client.view` |
| `PUT` | `/clients/{id}` | `client.update` |
| `POST` | `/clients/{id}/activate` | `client.update` |
| `POST` | `/clients/{id}/deactivate` | `client.update` |
| `DELETE` | `/clients/{id}` | `client.delete` |
| `GET` | `/clients/{clientId}/contacts` | `contact.view` |
| `POST` | `/clients/{clientId}/contacts` | `contact.create` |
| `GET` | `/clients/{clientId}/contacts/{id}` | `contact.view` |
| `PUT` | `/clients/{clientId}/contacts/{id}` | `contact.update` |
| `GET` | `/clients/{clientId}/terminals` | `terminal.view` |
| `POST` | `/clients/{clientId}/terminals` | `terminal.create` |
| `GET` | `/clients/{clientId}/terminals/{id}` | `terminal.view` |
| `PUT` | `/clients/{clientId}/terminals/{id}` | `terminal.update` |
| `GET` | `/clients/{clientId}/contracts` | `client.view` |
| `POST` | `/clients/{clientId}/contracts` | `client.create` |
| `GET` | `/terminal-assignments` | `client.view` |
| `GET` | `/terminal-assignments/{id}` | `client.view` |
| `GET` | `/clients/{clientId}/terminals/{termId}/warranties` | `client.view` |
| `POST` | `/clients/{clientId}/terminals/{termId}/warranties` | `client.create` |
| `GET` | `/dashboard/summary` | `client.view` |
| `POST` | `/import/clients` | `client.import` |
| `GET` | `/import/clients/template` | Público |
| `POST` | `/import/terminals` | `client.import` |
| `GET` | `/import/terminals/template` | Público |

### Web Routes

| Ruta | Controller | Permiso |
|------|------------|---------|
| `/clients` | `ClientController` | `client.view_all` |
| `/clients/new` | `ClientController` | `client.create` |
| `/clients/{id}` | `ClientController` | `client.view` |
| `/client/dashboard` | `ClientDashboardController` | `client.view` |
| `/client/import` | `ClientImportController` | `client.import` |
| `/clients/{clientId}/contacts/*` | `ContactController` | contact.* |
| `/clients/{clientId}/terminals/*` | `TerminalController` | terminal.* |
| `/clients/{clientId}/contracts/*` | `ContractController` | contract.* |
| `/clients/{clientId}/sites/*` | `SiteController` | site.* |
| `/clients/{clientId}/terminal-assignments/*` | `TerminalAssignmentController` | terminal.* |
| `/clients/{clientId}/terminals/{tId}/warranties/*` | `TerminalWarrantyController` | client.* |

---

## 10. Console Commands

| Comando | Nombre | Propósito |
|---------|--------|-----------|
| `client:check-expiring-warranties` | CheckExpiringWarranties | Verifica warranties próximas a vencer (30 días) |

---

## 11. Known Gaps / Próximos Pasos

| # | Gap | Prioridad |
|---|-----|-----------|
| G‑01 | **TerminalModel y TerminalWarranty sin ORM mapping**: Entidades definidas pero sin persistencia Doctrine | Alta |
| G‑02 | **Dashboard KPIs**: Solo `GetClientDashboardSummary` implementado. Faltan: contratos por vencer, top clientes, terminales sin asignación, crecimiento mensual | Media |
| G‑03 | **Importers**: ClientCsvFileParser + TerminalCsvFileParser existen. Falta UI completa y template descargable | Media |
| G‑04 | **Eventos sin suscriptores**: Muchos eventos definidos pero sin handlers cross-BC | Media |
| G‑05 | **Tests**: Solo 11 test files. Faltan tests para la mayoría de handlers | Alta |
| G‑06 | **PHP 8.4 enums**: Terminal.orm.xml usa `type="string"` con `enumType` attribute (no custom DBAL type). Revisar compatibilidad | Media |
