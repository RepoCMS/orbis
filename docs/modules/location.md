# Module: Location

## Overview

The **Location** Bounded Context provides geographic and hierarchical location data for the Orbis platform. It serves as a reference data service consumed by virtually all other bounded contexts for geographic context.

## Core Responsibilities

- Geographic hierarchy (Regions → Provinces → Cities)
- City search and autocomplete
- Location reference data
- Simple lookup queries

## Status

- **Progress:** ~95%
- **Tests:** ~7 files
- **Reports:** None
- **Importers:** None
- **Dashboard:** None (API-only)

## Domain Model

### Entity: Region

Administrative divisions (e.g., states, regions, departments).

### Entity: Province

Subdivisions of regions.

### Entity: City

Municipalities within provinces, with optional geographic coordinates.

## Commands

None (reference data module — no write operations)

## Queries

- `ListRegions` — All regions
- `ListProvinces` — All provinces
- `ListCities` — All cities
- `FindCityById` — Find city by ID
- `ListCitiesForProvince` — Cities in a province
- `SearchCities` — Search with autocomplete

## Cross-BC Integrations

| BC | Direction | Interaction |
|----|-----------|-------------|
| All BCs | Read | Geographic reference data |

## Pending Gaps

| Gap | Priority |
|-----|----------|
| Test coverage (only 7 files) | Medium |
| No web UI (API-only) | Low |
| Missing soft-delete on entity definitions | Medium |

## API Endpoints

| Method | Route | Permission |
|--------|-------|------------|
| GET | `/api/v1/locations/regions` | Public |
| GET | `/api/v1/locations/provinces` | Public |
| GET | `/api/v1/locations/cities` | Public |
| GET | `/api/v1/locations/cities/search` | Public |

---

*Note: Location BC is API-only with no web dashboard. AGENTS.md claims 100% complete but the actual spec status is ~95% with missing test coverage.*