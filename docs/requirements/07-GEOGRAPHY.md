# BC Geography / Location — Especificación de Dominio

**Propósito:** Catálogo geográfico de Ecuador — Regiones, Provincias y Ciudades. Datos de referencia usados por otros BCs.

**Estado:** 🟡 Domain implementado (entidades + VOs + Repository interfaces). Infrastructure, seeder y Presentation pendientes.

> **Nota:** El Bounded Context se llama `Location` en el código (`src/app/Location/`). El término "Geography" se usa en la documentación para distinguirlo de la entidad `Location` del BC Inventory (que representa almacenes/mochilas).

**Dependencias cross-BC:** Ninguna (este BC es dependencia de otros, no al revés)

---

## Entidades

### `Region`

Agrupación geográfica de nivel superior (ej: Costa, Sierra, Amazonía, Insular).

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| name | string | Nombre de la región |

---

### `Province`

Provincia del Ecuador, pertenece a una `Region`.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| regionId | ULID | Referencia a Region |
| name | string | Nombre de la provincia |

---

### `City`

Ciudad o cantón del Ecuador, pertenece a una `Province`.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| provinceId | ULID | Referencia a Province |
| name | string | Nombre de la ciudad |

---

## Estrategia de Datos

- **Carga inicial:** seeder/fixture con todos los datos de Ecuador (regiones, provincias, ciudades) cargados en BD al instalar el sistema
- **Mantenimiento:** CRUD desde la UI para agregar o modificar registros si es necesario
- **Nivel de granularidad:** Region → Province → City (el nivel Cantón queda fuera del alcance del MVP)
- **Solo lectura en el día a día:** los datos se cargan una vez y se consultan frecuentemente desde formularios

---

## Tareas Pendientes de Implementar

| Tarea | Estado |
|---|---|
| ORM XML mappings: Region, Province, City | ⚪ |
| Migraciones: tablas `regions`, `provinces`, `cities` | ⚪ |
| Seeder de datos geográficos de Ecuador | ⚪ |
| `DoctrineRegionRepository`, `DoctrineProvinceRepository`, `DoctrineCityRepository` | ⚪ |
| Application Queries: listar ciudades/provincias para formularios dinámicos | ⚪ |
| Presentation: endpoint API para selects en cascada (Region → Province → City) | ⚪ |

---

## Queries esperadas

- Listar todas las regiones
- Listar provincias por `regionId`
- Listar ciudades por `provinceId`
- Buscar ciudad por nombre (para autocompletar en formularios)

---

## Quién usa este BC

| BC | Uso |
|---|---|
| Client BC | `Site.cityId` — ciudad de la sucursal del cliente |
| Client BC | `ClientServiceZone.cities[]` — ciudades que abarca la zona de servicio |
| Inventory BC | `Location.city` — ciudad del almacén/laboratorio |
