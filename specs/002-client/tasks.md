# Tasks — Client Management

**Spec**: `specs/002-client/spec.md`
**Código**: `src/app/Client/`
**Tests reales**: 84 archivos (79 Unit + 5 Functional) — verificado 2026-08-07
**Estimado real**: ~92% implementado

> ⚠️ Este checklist fue realineado con el código en disco el 2026-08-07 (revisión T03).
> Los conteos anteriores ("11 test files", "27 tipos DBAL", "33 commands") estaban desactualizados.

---

## Leyenda

- ✅ Completado (verificado en disco)
- ⚠️ Parcial / con gaps (detalle en `spec.md` §11)
- ❌ Pendiente

---

## TASK-01: Domain Layer

### 1.1 Entities (9 reales)
- [x] `Client` — Aggregate root con ciclo de vida ACTIVE/INACTIVE + soft delete
- [x] `Contact` — Contactos por cliente con vinculación a User
- [x] `Terminal` — Terminales/cajeros con serial único + `atmModelCode`
- [x] `TerminalAssignment` — Asignación a sitio
- [x] `Site` — Sitio de instalación
- [x] `Contract` — Contrato de servicio
- [x] `TerminalModel` — Modelo de terminal (catálogo)
- [x] `TerminalWarranty` — Garantía de terminal
- [x] `ModelPart` — Partes de modelo (árbol jerárquico) — ⚠️ no estaba en el spec anterior

### 1.2 Value Objects (17 reales)
- [x] `ClientId`, `ClientName`, `ClientRuc`, `ContactId`, `ContactEmail`, `ContactPhoneNumber`, `ContactRole`, `ContractId`, `SerialNumber`, `SiteId`, `TerminalAssignmentId`, `TerminalId`, `TerminalIdAssignedByClient`, `TerminalModelId`, `TerminalName`, `TerminalWarrantyId`, `AtmModelCode`

### 1.3 Enums (10 reales)
- [x] `ClientStatus`, `ContactStatus`, `ContractStatus`, `SiteStatus`, `TerminalStatus`, `TerminalAssignmentStatus`, `TerminalFunctionality`, `TerminalType`, `TerminalVendor`, `WarrantyType`

### 1.4 Domain Events (29 reales, 16 publicados)
- [x] 29 eventos definidos (incluye `ClientSaleOrderCreated`, `ClientSaleOrderCompleted`, `ContactAccessProvisioned` — no estaban en el spec anterior)
- ⚠️ Solo 16 se publican desde handlers. Sin publisher: `ClientUpdated`, `ClientDeactivated`, `ClientReactivated`, `ClientDeleted`, `ContactAdded`, `ContactDeactivated`, `ContactReactivated`, `ContactUserLinked`, `TerminalRegistered`, `WarrantyExpired`, `SlaPolicyCreated`, `ClientSaleOrderCreated`, `ClientSaleOrderCompleted` → **G‑04/G‑08/G‑09** (spec §11)

### 1.5 Repositories
- [x] 8 interfaces: `ClientRepository`, `ContactRepository`, `ContractRepository`, `SiteRepository`, `TerminalAssignmentRepository`, `TerminalModelRepository`, `TerminalRepository`, `TerminalWarrantyRepository`
- [x] 1 Criteria: `ClientSearchCriteria`

### 1.6 Exceptions
- [x] 22 excepciones de dominio

---

## TASK-02: Application Layer

### 2.1 Commands (37 reales: 35 sync + 2 async)
- [x] 35 sync con Handler + Validación
- [x] 2 async: `ImportClientsMessage`, `ImportTerminalsMessage` (Messenger async_events) — ⚠️ sin dispatcher activo (G‑12)
- [x] `ImportTerminalModels`, `ImportModelParts` (P2) — solo CLI (G‑13)

### 2.2 Queries (16 reales)
- [x] 16 queries: findAll/findById por cada entidad + dashboard summary
- ⚠️ `ListContactsByClient` sin caller en producción (G‑11)

### 2.3 Response DTOs (7 reales)
- [x] `ClientResponse`, `ContactResponse`, `ContractResponse`, `SiteResponse`, `TerminalAssignmentResponse`, `TerminalResponse`, `WarrantyResponse`
- [x] `ClientDashboardSummary` DTO (11 KPIs)

### 2.4 Application Exceptions (9 reales)
- [x] `ClientNotFoundException`, `ClientRucAlreadyExistsException`, `ContactNotFoundException`, `ContractNotFoundException`, `SerialForTerminalAlreadyExistsException`, `SiteNotFoundException`, `TerminalAssignmentNotFoundException`, `TerminalNotFoundException`, `TerminalWarrantyNotFoundException`

### 2.5 Reportes (2 reales)
- [x] `ContractsExpiringReportGenerator` — contratos activos por vencer ≤90 días (Excel/PDF vía `ClientReportController`)
- [x] `TerminalsByClientReportGenerator` — terminales por cliente con asignación y sitio (Excel/PDF)

---

## TASK-03: Infrastructure Layer

### 3.1 Doctrine Repositories (8 reales)
- [x] `DoctrineClientRepository`, `DoctrineContactRepository`, `DoctrineContractRepository`, `DoctrineSiteRepository`, `DoctrineTerminalAssignmentRepository`, `DoctrineTerminalModelRepository`, `DoctrineTerminalRepository`, `DoctrineTerminalWarrantyRepository`

### 3.2 ORM Mappings (8 reales — G‑01 resuelto)
- [x] `Client.orm.xml` — table `client_clients`
- [x] `Contact.orm.xml` — table `client_contacts`
- [x] `Site.orm.xml` — table `client_sites`
- [x] `Terminal.orm.xml` — table `client_terminals` (custom DBAL types — G‑05 resuelto)
- [x] `TerminalAssignment.orm.xml` — table `client_terminal_assignments`
- [x] `TerminalModel.orm.xml` — table `client_terminal_models`
- [x] `TerminalWarranty.orm.xml` — table `client_terminal_warranties`
- [x] `ModelPart.orm.xml` — table `client_model_parts`

### 3.3 Custom DBAL Types (28 reales)
- [x] `client_id`, `client_name`, `client_ruc`, `client_status`, `contact_id`, `contact_email`, `contact_phone_number`, `contact_role`, `contact_status`, `contract_id`, `contract_status`, `site_id`, `site_status`, `terminal_id`, `terminal_assignment_id`, `terminal_id_assigned_by_client`, `terminal_model_id`, `terminal_name`, `terminal_serial`, `terminal_status`, `terminal_type`, `terminal_vendor`, `terminal_functionality`, `terminal_warranty_id`, `warranty_type`, `atm_model_code`, `model_part_id`, `terminal_assignment_status`

### 3.4 Console Commands (5 reales — el spec anterior decía 1)
- [x] `client:check-expiring-warranties` — ⚠️ no publica `WarrantyExpired` (G‑08)
- [x] `client:import:clients`
- [x] `client:import:terminals`
- [x] `client:import:terminal-models`
- [x] `client:import:model-parts`

### 3.5 Import (6 archivos reales)
- [x] `ClientCsvFileParser`, `ClientRowValidator`
- [x] `TerminalCsvFileParser`, `TerminalRowValidator`
- [x] `TerminalModelCsvFileParser` (P2)
- [x] `ModelPartCsvFileParser` (P2)

### 3.6 Messenger
- [x] `ContactStatusCascadeHandler` — async, escucha `ContactDeactivated`/`ContactReactivated` — ⚠️ **inerte** (eventos nunca publicados, G‑04)
- [x] `ImportClientsMessage` / `ImportTerminalsMessage` → routing `async_events` en `messenger.yaml` — ⚠️ sin dispatcher (G‑12)

---

## TASK-04: Presentation Layer

### 4.1 API Controllers (8 reales, 43 rutas)
- [x] `ApiClientController` — CRUD clientes + activate/reactivate
- [x] `ApiClientContactController` — CRUD + link-user/unlink-user/provision-access/activate/reactivate
- [x] `ApiClientContractController` — CRUD + cancel
- [x] `ApiClientTerminalController` — CRUD + activate/reactivate
- [x] `ApiClientTerminalAssignmentController` — CRUD + activate/reactivate
- [x] `ApiClientTerminalWarrantyController` — CRUD
- [x] `ApiClientDashboardController` — Dashboard summary
- [x] `ApiClientImportController` — Importación CSV + templates descargables

### 4.2 Web Controllers (10 reales — incluye ClientReportController, no estaba en spec anterior)
- [x] `ClientController`
- [x] `ClientDashboardController`
- [x] `ClientImportController`
- [x] `ClientReportController` — reportes Excel/PDF (contratos por vencer, terminales por cliente)
- [x] `ContactController`
- [x] `ContractController`
- [x] `SiteController`
- [x] `TerminalController`
- [x] `TerminalAssignmentController`
- [x] `TerminalWarrantyController`

---

## TASK-05: Tests (84 archivos reales — 79 Unit + 5 Functional)

### 5.1 Unit Tests — Domain (8)
- [x] `ClientTest`, `ContactTest`, `ContractTest`, `SiteTest`, `TerminalAssignmentTest`, `TerminalModelTest`, `TerminalTest`, `TerminalWarrantyTest`

### 5.2 Unit Tests — Command Handlers (24)
- [x] `ImportClientsHandlerTest`, `ImportClientsMessageHandlerTest`, `ImportTerminalsHandlerTest`, `ImportTerminalsMessageHandlerTest`, `ImportTerminalModelsHandlerTest`, `ActivateTerminalHandlerTest`, `CancelContractHandlerTest`, `CreateContractHandlerTest`, `DeactivateSiteHandlerTest`, `DeactivateTerminalAssignmentHandlerTest`, `DeactivateTerminalHandlerTest`, `LinkUserToContactHandlerTest`, `ProvisionContactAccessHandlerTest`, `ReactivateSiteHandlerTest`, `RegisterTerminalAssignmentHandlerTest`, `RegisterWarrantyHandlerTest`, `SoftDeleteContactHandlerTest`, `SoftDeleteTerminalHandlerTest`, `UnlinkUserFromContactHandlerTest`, `UpdateContactHandlerTest`, `UpdateContractHandlerTest`, `UpdateSiteHandlerTest`, `UpdateTerminalAssignmentHandlerTest`, `UpdateTerminalHandlerTest`, `UpdateWarrantyHandlerTest`
- ❌ Sin test: `RegisterClientHandler`, `UpdateClientHandler`, `ActivateClientHandler`, `DeactivateClientHandler`, `DeleteClientHandler`, `AddContactHandler`, `DeactivateContactHandler`, `ReactivateContactHandler`, `ActivateTerminalAssignmentHandler`, `DeactivateTerminalAssignmentHandler`, `ActivateTerminalHandler` (✅ existe), `ImportModelPartsHandler`, `RegisterTerminalHandler` → **G‑06**

### 5.3 Unit Tests — Query Handlers (15)
- [x] `FindClientByIdHandlerTest`, `FindContactByIdHandlerTest`, `FindContactsByClientHandlerTest`, `FindContractByIdHandlerTest`, `FindContractsByClientHandlerTest`, `FindSiteByIdHandlerTest`, `FindTerminalAssignmentByIdHandlerTest`, `FindTerminalByIdHandlerTest`, `FindTerminalsByClientHandlerTest`, `FindWarrantiesByTerminalHandlerTest`, `FindWarrantyByIdHandlerTest`, `GetClientDashboardSummaryHandlerTest`, `ListClientsHandlerTest`, `ListContactsByClientHandlerTest`, `ListSitesByClientHandlerTest`, `ListTerminalAssignmentsHandlerTest`

### 5.4 Functional Tests (5)
- [x] `ApiClientControllerTest`, `ClientControllerTest`, `DashboardControllerTest`, `TerminalControllerTest` (+ 1 adicional)

### 5.5 Coverage Gaps
- ⚠️ Handlers de clientes (register/update/activate/deactivate/delete) y contactos (add/deactivate/reactivate) sin test unitario
- ⚠️ Reportes sin test
- ⚠️ Domain Events sin test de publicación

---

## TASK-06: Known Gaps (estado REAL — ver spec.md §11)

| # | Gap | Estado |
|---|-----|--------|
| G‑01 | TerminalModel/TerminalWarranty/ModelPart sin ORM mapping | ✅ **Resuelto** (8 mappings) |
| G‑02 | Dashboard KPIs incompletos | ✅ **Resuelto** (11 KPIs) |
| G‑03 | Template descargable para imports | ✅ **Resuelto** (API + web) |
| G‑04 | Publicación inconsistente de Domain Events (16/29) — cascada Contact→User inerte | ⚠️ **Vigente — prioridad alta** |
| G‑05 | Terminal.orm.xml `type="string"` + `enumType` | ✅ **Resuelto** (28 custom DBAL types) |
| G‑06 | Cobertura de tests (faltan handlers de clientes/contactos) | ⚠️ **Vigente** (84 archivos reales ya) |
| G‑08 | `WarrantyExpired` sin publisher | ⚠️ **Vigente** |
| G‑09 | Dead events (`ClientSaleOrderCreated`, `ClientSaleOrderCompleted`, `SlaPolicyCreated`) | ⚠️ **Vigente** |
| G‑10 | Bug SQL dashboard: tabla `contacts` vs `client_contacts` | ⚠️ **Vigente** |
| G‑11 | `ListContactsByClientQuery` sin caller en producción | ⚠️ **Vigente** |
| G‑12 | Importers async sin dispatcher | ⚠️ **Vigente (baja)** |
| G‑13 | UI de modelos/partes solo CLI | ⚠️ **Vigente (baja)** |
