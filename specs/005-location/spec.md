# Feature Specification: Geography (Location)

**Spec path**: `specs/005-location/spec.md`
**Created**: 2026-06-17
**Status**: Completado ~95% (código listo, sin tests)

---

## 1. Descripción General

El Bounded Context **Location** es un BC de solo lectura que provee datos geográficos de Ecuador (regiones, provincias, ciudades). Es referenciado por Client BC (cities en Sites) y Organization BC (cities en ServiceZones).

---

## 2. Business Rules

| # | Regla |
|---|-------|
| LO‑01 | **Solo lectura**: No hay commands, solo queries. Los datos se cargan vía seed command. |
| LO‑02 | **Jerarquía**: Región → Provincia → Ciudad. Una ciudad pertenece a exactamente una provincia. |
| LO‑03 | **Seed data**: Las ciudades se cargan con `SeedGeographyCommand`. No hay CRUD desde UI. |

---

## 3. Entities

| Entidad | Descripción |
|---------|-------------|
| `Region` | Región geográfica (Costa, Sierra, Oriente, Galápagos) |
| `Province` | Provincia con `regionId` |
| `City` | Ciudad con `provinceId` y `name` |

---

## 4. Queries

| Query | Descripción |
|-------|-------------|
| `ListRegions` | Lista todas las regiones |
| `ListProvinces` | Lista provincias (opcional: por región) |
| `ListCities` | Lista ciudades (opcional: por provincia) |
| `FindCityById` | Busca ciudad por ULID |
| `SearchCities` | Búsqueda textual de ciudades |

---

## 5. Known Gaps

| # | Gap | Prioridad |
|---|-----|-----------|
| G‑01 | **Tests**: 0 tests (AGENTS.md dice 40 tests / 13 files). Crear tests para entities + queries. | Alta |
