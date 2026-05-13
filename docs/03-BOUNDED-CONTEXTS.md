# Bounded Contexts — Orbis

El sistema se organiza en **8 Bounded Contexts** + Shared Kernel. Cada BC es dueño exclusivo de sus datos y se comunica con otros BCs **solo por referencia de ID (ULID)**. Nunca se instancian entidades de otro BC dentro de un BC.

---

## Estado de Implementación

| BC | Domain | Application | Infrastructure | Presentation | Estado General |
|---|---|---|---|---|---|
| **Identity** | ✅🧪 | ✅ | ✅ | ✅ | Funcional (MFA Config pendiente) |
| **Organization** | ✅🧪 | ✅ | ✅ | ✅ | Completo |
| **Client** | ✅🧪 | ✅ | ✅ | ✅ | Completo |
| **Location** | ✅ | ⚪ | ⚪ | ⚪ | Solo dominio |
| **Inventory** | ✅🧪 | ⚪ | ✅ | ⚪ | Domain + Infra |
| **Support** | ⚪ | ⚪ | ⚪ | ⚪ | No iniciado |
| **Expense** | ⚪ | ⚪ | ⚪ | ⚪ | No iniciado |
| **Notification** | ⚪ | ⚪ | ⚪ | ⚪ | No iniciado |

---

## Mapa de Dependencias entre BCs

```
Identity ◄─── Organization (Employee.userId)
Identity ◄─── Client      (Contact.userId?)
Identity ◄─── Support     (tickets → users asignados)
Client   ◄─── Support     (tickets → clients, zones)
Inventory ◄── Support     (repuestos en tickets)
Location ◄─── Client      (Site → city)
Location ◄─── Support     (ubicación de terminales)
Location ◄─── Inventory   (Location → city?)
Support  ◄─── Expense     (gastos → tickets)
```

---

## Identity BC

**Propósito:** Autenticación, autorización y auditoría de seguridad. No contiene datos personales.

**Aggregate Roots:** `User`, `Role`

**Entidades:** `Permission`, `UserToken`, `UserMfaConfig`, `UserMfaBackupCode`, `AuthLog`

**Estado:** ✅ Domain + Application + Infrastructure + Web (parcial — `UserMfaBackupCode` pendiente)

**Reglas clave:**
- `User` solo contiene credenciales + estado de acceso
- Los datos personales (nombre, email, teléfono) viven en el BC que los necesita (Organization, Client)
- `Role` es dinámico (administrable desde UI)
- `Permission` tiene formato `{módulo}.{acción}` (ej: `user.create`, `ticket.assign`) — fijo, sembrado
- MFA TOTP es **obligatorio** para todos los usuarios

**Roles del sistema** (fijos, sembrados):

| Slug | Nombre |
|---|---|
| `ADMIN` | Administrador |
| `SUPERVISOR` | Supervisor |
| `TECHNICIAN` | Técnico de Campo |
| `FINANCIAL` | Financiero |
| `WAREHOUSE` | Bodeguero |
| `CLIENT_PORTAL` | Cliente (Portal) |

**Especificación completa:** [docs/requirements/01-IDENTITY.md](requirements/01-IDENTITY.md)

---

## Organization BC

**Propósito:** Gestión del personal interno de la empresa (empleados, zonas de servicio).

**Aggregate Root:** `Employee`, `ServiceZone`

**Entidades:** `EmploymentPeriod`, `EmployeeSpecialPeriod`

**Estado:** 🟡 Domain ✅🧪 | Application ✅ | Web básico ✅ | `ServiceZone` ⚪ Pendiente

**Reglas clave:**
- `Employee.userId` referencia a `User` (Identity BC) — nunca instancia User
- `Employee.serviceZoneId` referencia a `ServiceZone` — permite detectar atenciones fuera de zona
- Al crear un empleado, se crea su `User` en la misma transacción (`CreateEmployeeWithUserCommand`)
- Al desactivar/eliminar un Employee, se desactiva/elimina su User en cascada
- `ServiceZone` agrupa `Site`s de clientes bajo un área operativa de la empresa (≠ zona del cliente)

**Especificación completa:** [docs/requirements/03-ORGANIZATION.md](requirements/03-ORGANIZATION.md)

---

## Client BC

**Propósito:** Gestión de clientes (bancos), su parque de cajeros (modelos + terminales), sucursales y contactos. También gestión de contratos y zonas de cobertura.

**Aggregate Roots:** `Client`

**Entidades:** `Contact`, `Catalogue`, `Terminal`, `TerminalAssignment`, `Site`, `ClientServiceZone`, `Contract`, `ContractCoverage`, `SlaPolicy`, `TerminalWarranty`, `ClientSaleOrder`, `ClientSaleOrderItem`

**Estado:** ✅ Completo (implementación actual: Client, Contact, Catalogue, Terminal, Site)

**Reglas clave:**
- Un `Client` debe tener siempre al menos un `Contact` activo
- `Contact.userId?` referencia a `User` (Identity BC) — solo si tiene acceso al portal
- `CreateClientCommand` incluye obligatoriamente el primer `Contact` en la misma transacción
- Al desactivar/eliminar un contacto con `userId`, se desactiva el `User` en Identity BC
- RUC ecuatoriano con validación del dígito verificador

**Especificación completa:** [docs/requirements/02-CLIENT.md](requirements/02-CLIENT.md)

---

## Location BC (Geography)

**Propósito:** Catálogo geográfico de Ecuador (Regiones, Provincias, Ciudades). Datos de solo lectura.

**Entidades:** `Region`, `Province`, `City`

**Estado:** 🟡 Domain ✅ | Infrastructure ⚪ | Seeder ⚪

**Pendiente:** XML mappings, Doctrine repositories, migración, seeder de datos geográficos de Ecuador, queries para formularios.

**Nota:** Datos de solo lectura — se poblan con un seeder. Son usados por Client BC (Site → city) y Support BC (ubicación de terminales).

**Especificación completa:** [docs/requirements/07-GEOGRAPHY.md](requirements/07-GEOGRAPHY.md)

---

## Inventory BC

**Propósito:** Gestión de repuestos, almacenes, laboratorios y ciclo de vida de componentes (serializado y no serializado). Reparaciones, solicitudes y órdenes de compra de repuestos.

**Aggregate Roots:** `Product`, `Location`, `PurchaseOrder`, `Entry`, `Repair`, `Request`

**Entidades:** `SerializedStock`, `NonserializedStock`, `Movement`, `PurchaseOrderItem`, `EntryItem`, `Transformation`, `StockMinimum`, `SerialHistory`

**Estado:** 🟡 Domain ✅🧪 | Infrastructure ✅ | Application ⚪ | Presentation ⚪

**Reglas clave:**
- Stock serializado tiene serial único y ciclo AVAILABLE→RESERVED→DAMAGED→DECOMMISSIONED
- Stock no serializado se gestiona por lotes con status: AVAILABLE, RESERVED, DAMAGED, DECOMMISSIONED
- Solicitudes (Request) requieren aprobación del supervisor antes de ejecutar el movimiento
- Técnico puede rechazar una solicitud aprobada (AWAITING_TECHNICIAN_ACCEPTANCE)
- Repuesto dañado desde mochila va primero a almacén, luego a laboratorio (no directamente)

**Especificación completa:** [docs/requirements/05-INVENTORY.md](requirements/05-INVENTORY.md)

---

## Support BC

**Propósito:** Gestión del ciclo de vida completo de tickets de servicio técnico de cajeros automáticos.

**Aggregate Roots:** `Ticket`

**Entidades:** `TicketTechnician`, `Journey`, `TicketFinding`, `TicketSparePart`, `TicketPendingSparePart`, `TicketEvidence`, `TicketPause`, `TicketReschedule`

**Estado:** ⚪ No iniciado (Fase 10 — BC de mayor complejidad)

**Dependencias:** Identity, Client, Inventory, Location

**Reglas clave:**
- Tipos de servicio: CORRECTIVO, MANTENIMIENTO, POTENCIACION
- CAT: documento PDF con información técnica del ATM (1 por ATM, versionable)
- Journey: registro del desplazamiento técnico al site (OUTWARD / NEXT_SITE / RETURN)
- Coordinador puede editar ticket hasta estado APPROVED
- Repuestos de emergencia se registran sin solicitud formal de inventario

**Especificación completa:** [docs/requirements/04-SUPPORT.md](requirements/04-SUPPORT.md)

---

## Expense BC

**Propósito:** Gestión de gastos operativos relacionados a la resolución de incidencias (tickets).

**Aggregate Roots:** `OperatingExpense`

**Entidades:** `ExpenseItem`, `CashAdvance`, `AfterHourTrip`

**Estado:** ⚪ No iniciado (Fase 12)

**Reglas clave:**
- 11 tipos de gasto (UBER_EMPRESARIAL, UBER_EFECTIVO, TAXI, BUS, BUS_INTERPROVINCIAL, AVION, HOTEL, ALIMENTACION, OTROS_ALIMENTACION, ENCOMIENDAS, INDUMENTARIA, INSUMOS)
- Período de liquidación: ciclo mensual con balance positivo/negativo
- Flujo de aprobación Finance: DRAFT → SUBMITTED → APPROVED / REJECTED

**Especificación completa:** [docs/requirements/06-EXPENSE.md](requirements/06-EXPENSE.md)

---

## Notification BC

**Propósito:** Envío de notificaciones por EMAIL y WhatsApp a usuarios, técnicos y clientes.

**Entidades:** `NotificationPreference`, `NotificationTemplate`, `NotificationLog`

**Estado:** ⚪ No iniciado (Fase 13)

**Reglas clave:**
- Canales: EMAIL + WhatsApp
- Preferencias de notificación por usuario/canal/tipo
- Integración con Domain Events de otros BCs

**Especificación completa:** [docs/requirements/08-NOTIFICATION.md](requirements/08-NOTIFICATION.md)

---

## Reglas Cross-BC

**Especificación completa:** [docs/requirements/09-CROSS-BC.md](requirements/09-CROSS-BC.md)

| Regla | Descripción |
|---|---|
| REQ-CST-001 | Employee desactivado/eliminado/reactivado → User en cascada |
| REQ-CST-002 | Client desactivado/eliminado → Contacts → Users en cascada |
| REQ-CST-003 | Contact desactivado/eliminado/reactivado → User en cascada |
| REQ-CST-004 | Guards: entidad padre inactiva/eliminada bloquea creación de dependientes |
| REQ-CST-005 | User INACTIVE/deleted no puede autenticarse (Symfony Security) |

---

## Shared Kernel

**Propósito:** Abstracciones técnicas reutilizables. No contiene lógica de negocio.

**Ubicación:** `src/app/Shared/`

**Contenido:**
- `AbstractUlid` — base para todos los IDs (ULID, genera y valida)
- `DomainException` / `ApplicationException` — excepciones base
- `AbstractStringValueObject` — base para VOs de string
- Custom Doctrine types base (ULID, string VO, enum)
- `TimestampsSubscriber` — gestiona `createdAt`/`updatedAt` automáticamente
- `DomainExceptionListener` — convierte excepciones de dominio a HTTP 400

**Regla:** Si el Shared Kernel crece más allá de abstracciones técnicas, es una señal de mal diseño. Ningún BC depende de otro BC; todos solo dependen del Shared Kernel.

---

## Comunicación entre BCs

```php
// ✅ Correcto — referenciar por ID
class Employee {
    private UserIdValueObject $userId; // Solo el ID, nunca el User completo
}

// ✅ Correcto — Application Service coordina BCs
class CreateEmployeeWithUserHandler {
    public function __invoke(CreateEmployeeWithUserCommand $command): void
    {
        // 1. Crear User en Identity BC
        // 2. Crear Employee en Organization BC
        // Todo en la misma transacción
    }
}

// ❌ Incorrecto — importar entidad de otro BC
use App\Identity\Domain\Entities\User; // NUNCA en Organization BC
```
