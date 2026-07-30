# Tasks — Client Management

**Spec**: `specs/002-client/spec.md`
**Código**: `src/app/Client/`
**Tests**: 11 test files (Unit) + 1 Integration
**Estimado**: ~70% implementado

---

## Leyenda

- ✅ Completado
- ⏳ En progreso
- ❌ Pendiente

---

## TASK-01: Domain Layer

### 1.1 Entities
- [x] `Client` — Aggregate root con ciclo de vida ACTIVE/INACTIVE + soft delete
- [x] `Contact` — Contactos por cliente con vinculación a User
- [x] `Terminal` — Terminales/cajeros con serial único
- [x] `TerminalAssignment` — Asignación a sitio
- [x] `Site` — Sitio de instalación
- [x] `Contract` — Contrato de servicio
- [x] `TerminalModel` — Modelo de terminal (catálogo)
- [x] `TerminalWarranty` — Garantía de terminal

### 1.2 Value Objects
- [x] 17 VOs: `ClientId`, `ClientName`, `ClientRuc`, `ContactId`, `ContactEmail`, `ContactPhoneNumber`, `ContactRole`, `ContractId`, `SerialNumber`, `SiteId`, `TerminalAssignmentId`, `TerminalId`, `TerminalIdAssignedByClient`, `TerminalModelId`, `TerminalName`, `TerminalWarrantyId`, `AtmModelCode`

### 1.3 Enums
- [x] `ClientStatus` — `active`, `inactive`
- [x] `ContactStatus` — `active`, `inactive`
- [x] `ContractStatus` — `active`, `expired`, `cancelled`
- [x] `SiteStatus` — `active`, `inactive`
- [x] `TerminalStatus` — `active`, `inactive`
- [x] `TerminalAssignmentStatus` — `operative`, `inoperative`
- [x] `TerminalFunctionality` — `dispenser`, `recycler`, `multinfunction`, `counter`
- [x] `TerminalType` — `lobby`, `ttw`
- [x] `TerminalVendor` — `hyosung`, `diebold`, `ncr`, `grg`
- [x] `WarrantyType` — `manufacturer`, `extended`

### 1.4 Domain Events
- [x] 29 eventos: creación, actualización, activación/desactivación de clientes, contactos, terminales, asignaciones, warranties, contratos

### 1.5 Repositories
- [x] 8 interfaces: `ClientRepository`, `ContactRepository`, `ContractRepository`, `SiteRepository`, `TerminalAssignmentRepository`, `TerminalModelRepository`, `TerminalRepository`, `TerminalWarrantyRepository`
- [x] 1 Criteria: `ClientSearchCriteria`

### 1.6 Exceptions
- [x] 22 excepciones de dominio

---

## TASK-02: Application Layer

### 2.1 Commands
- [x] 33 commands sync + 2 async (ImportClients, ImportTerminals)
- [x] Todos con Handler + Validación

### 2.2 Queries
- [x] 16 queries: findAll/findById por cada entidad + dashboard summary

### 2.3 Response DTOs
- [x] 7 responses: `ClientResponse`, `ContactResponse`, `ContractResponse`, `SiteResponse`, `TerminalAssignmentResponse`, `TerminalResponse`, `WarrantyResponse`

### 2.4 Application Exceptions
- [x] `ClientNotFoundException`, `ClientRucAlreadyExistsException`
- [x] `ContactNotFoundException`
- [x] `ContractNotFoundException`
- [x] `SerialForTerminalAlreadyExistsException`
- [x] `SiteNotFoundException`
- [x] `TerminalAssignmentNotFoundException`
- [x] `TerminalNotFoundException`
- [x] `TerminalWarrantyNotFoundException`

---

## TASK-03: Infrastructure Layer

### 3.1 Doctrine Repositories
- [x] 8 implementaciones: `DoctrineClientRepository`, `DoctrineContactRepository`, `DoctrineContractRepository`, `DoctrineSiteRepository`, `DoctrineTerminalAssignmentRepository`, `DoctrineTerminalModelRepository`, `DoctrineTerminalRepository`, `DoctrineTerminalWarrantyRepository`

### 3.2 ORM Mappings
- [x] `Client.orm.xml` — table `client_clients`
- [x] `Contact.orm.xml` — table `client_contacts`
- [x] `Site.orm.xml` — table `client_sites`
- [x] `Terminal.orm.xml` — table `client_terminals`
- [x] `TerminalAssignment.orm.xml` — table `client_terminal_assignments`
- ✅ `TerminalModel.orm.xml` — table `client_terminal_models`
- ✅ `TerminalWarranty.orm.xml` — table `client_terminal_warranties`

### 3.3 Custom DBAL Types
- [x] 27 tipos: `client_id`, `client_name`, `client_ruc`, `client_status`, `contact_id`, `contact_email`, `contact_phone_number`, `contact_role`, `contact_status`, `contract_id`, `contract_status`, `site_id`, `site_status`, `terminal_id`, `terminal_assignment_id`, `terminal_id_assigned_by_client`, `terminal_model_id`, `terminal_name`, `terminal_serial`, `terminal_status`, `terminal_type`, `terminal_vendor`, `terminal_functionality`, `terminal_warranty_id`, `warranty_type`, `atm_model_code`

### 3.4 Console Commands
- [x] `client:check-expiring-warranties`

### 3.5 Import
- [x] `ClientCsvFileParser`, `ClientRowValidator`
- [x] `TerminalCsvFileParser`, `TerminalRowValidator`

### 3.6 Messenger
- [x] `ContactStatusCascadeHandler` — async

---

## TASK-04: Presentation Layer

### 4.1 API Controllers
- [x] `ApiClientController` — CRUD clientes
- [x] `ApiClientContactController` — CRUD contactos
- [x] `ApiClientContractController` — CRUD contratos
- [x] `ApiClientTerminalController` — CRUD terminales
- [x] `ApiClientTerminalAssignmentController` — CRUD asignaciones
- [x] `ApiClientTerminalWarrantyController` — CRUD warranties
- [x] `ApiClientDashboardController` — Dashboard summary
- [x] `ApiClientImportController` — Importación CSV

### 4.2 Web Controllers
- [x] `ClientController`
- [x] `ClientDashboardController`
- [x] `ClientImportController`
- [x] `ContactController`
- [x] `ContractController`
- [x] `SiteController`
- [x] `TerminalController`
- [x] `TerminalAssignmentController`
- [x] `TerminalWarrantyController`

---

## TASK-05: Tests

### 5.1 Unit Tests
- [ ] `ClientTest` — ❌ Pendiente
- [x] `ContactTest` — ✅ exists
- [x] Handlers tests — ✅ algunos existen (`CreateClientHandlerTest`, etc.)
- [x] Query tests — ✅ `GetClientDashboardSummaryHandlerTest` exists

### 5.2 Integration Tests
- [x] `ClientContactCascadeTest` — ✅ exists (1 integration test)

### 5.3 Coverage Gaps
- [x] Tests para commands: **11 test files** de ~33 commands — ❌ Faltan ~22 handlers sin test
- [x] Tests para queries: **1 query test** de 16 — ❌ Faltan 15
- [x] Tests para importers — ❌ Pendiente
- [x] Tests para Domain Events — ❌ Pendiente

---

## TASK-06: Known Gaps

| # | Gap | Estado |
|---|-----|--------|
| G‑01 | TerminalModel y TerminalWarranty sin ORM mapping | ✅ Creados |
| G‑02 | Dashboard KPIs incompletos | ✅ Implementados (contratos por vencer, top clientes, contactos, terminales sin asignación, crecimiento mensual) |
| G‑03 | Template descargable para imports | ✅ API endpoints + web template creados |
| G‑04 | Tests coverage insuficiente (solo 11 + 1 archivos) | ❌ Necesita expandirse |
| G‑05 | Terminal.orm.xml usa `type="string"` con `enumType` en vez de custom DBAL type (PHP 8.4) | ❌ Revisar compatibilidad |
