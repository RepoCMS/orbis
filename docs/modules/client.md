# Module: Client

## Overview

The **Client** Bounded Context manages customer relationships, contracts, sites, terminals, and contacts within the Orbis platform. It handles client lifecycle, terminal inventory assignment, and contract management.

## Core Responsibilities

- Client registration and lifecycle management
- Contract creation and lifecycle
- Terminal registration and assignment
- Site management (geographic locations)
- Contact management within client organizations
- Importer infrastructure for CSV/Excel data

## Status

- **Progress:** ~70% (per CONTEXT.md)
- **Tests:** ~80 files
- **Reports:** ContractsExpiring, TerminalsByClient
- **Importers:** Clientes, Terminales, Modelos, Partes de Modelo

## Domain Model

### Aggregate: Client

| Field | Type | Description |
|-------|------|-------------|
| `id` | `ClientIdValueObject` (ULID) | Unique identifier |
| `name` | `ClientNameValueObject` | Client name |
| `status` | `ClientStatus` | Active/inactive |
| `createdAt` | `DateTimeImmutable` | Creation timestamp |

### Aggregate: Contract

Tracks contract details, expiration, and terms with associated terminals and sites.

### Aggregate: Terminal

Represents ATM/field devices with status, model, serial number, and assignment tracking.

### Aggregate: Contact

Represents individuals within client organizations, with optional user linking.

## Commands

- `RegisterClient` — Create new client
- `UpdateClient` — Update client info
- `DeactivateClient` — Deactivate client
- `ActivateClient` — Reactivate client
- `CancelContract` — Cancel active contract
- `CreateContract` — Create new contract
- `UpdateContract` — Update contract terms
- `RegisterTerminal` — Register new terminal
- `ActivateTerminal` — Activate terminal
- `DeactivateTerminal` — Deactivate terminal
- `UpdateTerminal` — Update terminal info
- `SoftDeleteTerminal` — Soft-delete terminal
- `RegisterTerminalAssignment` — Assign terminal to site
- `ActivateTerminalAssignment` — Activate assignment
- `DeactivateTerminalAssignment` — Deactivate assignment
- `UpdateTerminalAssignment` — Update assignment
- `AddContact` — Add contact to client
- `UpdateContact` — Update contact info
- `DeactivateContact` — Deactivate contact
- `ReactivateContact` — Reactivate contact
- `SoftDeleteContact` — Soft-delete contact
- `AddSite` — Add site to client
- `UpdateSite` — Update site info
- `ActivateSite` — Activate site
- `DeactivateSite` — Deactivate site
- `RegisterWarranty` — Register warranty for terminal
- `UpdateWarranty` — Update warranty
- `LinkUserToContact` — Link Identity User to Contact
- `UnlinkUserFromContact` — Remove link
- `ProvisionContactAccess` — Create user for contact
- `ImportClients` — Import clients from CSV/Excel
- `ImportTerminals` — Import terminals from CSV/Excel
- `ImportTerminalModels` — Import terminal models
- `ImportModelParts` — Import parts for terminal models

## Queries

- `ListClients` — List all clients
- `FindClientById` — Find client by ID
- `ListContactsByClient` — List contacts for a client
- `FindContactById` — Find contact by ID
- `FindContractsByClient` — List contracts for client
- `FindContractById` — Find contract by ID
- `FindSiteById` — Find site by ID
- `ListSitesByClient` — List sites for client
- `ListTerminalAssignments` — List terminal assignments
- `FindTerminalAssignmentById` — Find assignment by ID
- `FindTerminalsByClient` — List terminals for client
- `FindWarrantiesByTerminal` — List warranties for terminal
- `FindWarrantyById` — Find warranty by ID
- `FindTerminalsByClient` — List terminals for client
- `GetClientDashboardSummary` — Dashboard KPIs

## Cross-BC Integrations

| BC | Direction | Interaction |
|----|-----------|-------------|
| Identity | Read | Resolves User entities for contact linking |
| Identity | Write | `UnlinkUserFromContact`, `LinkUserToContact` |
| Shared Kernel | Both | Import infrastructure |
| Organization | Read | Resolves Employee references |

## Importers (Implemented)

1. **Import Clientes** — CSV/Excel client import
2. **Import Terminales** — Terminal import
3. **Import Modelos de Terminal** — Terminal model import
4. **Import Partes de Modelo** — Spare parts for models

## Reports

1. **ContractsExpiring** — Contracts expiring within date range
2. **TerminalsByClient** — Terminal count per client

## Pending Gaps

| Gap | Priority |
|-----|----------|
| Importers P2/P3 | High |
| Missing test coverage | Medium |
| Complete spec alignment | High |

## API Endpoints

| Method | Route | Permission |
|--------|-------|------------|
| GET | `/client/clients` | `clients.view` |
| POST | `/client/clients` | `clients.create` |
| GET | `/client/clients/{id}` | `clients.view` |
| PUT | `/client/clients/{id}` | `clients.update` |
| POST | `/client/clients/{id}/deactivate` | `clients.update` |
| POST | `/client/clients/{id}/activate` | `clients.update` |

*(Full API endpoint list in spec)*