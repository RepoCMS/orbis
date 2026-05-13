# BC Expense — Especificación de Dominio

**Propósito:** Gestión de gastos operativos de técnicos: adelantos de efectivo, liquidación de gastos por período, aprobación de finanzas y viajes fuera de horario.

**Estado:** ⚪ Pendiente. Sin código implementado. Tercera prioridad tras Support.

**Dependencias cross-BC:** Identity (usuarios), Organization (empleados), Support (tickets), Client (zonas de servicio)

---

## Contexto Operativo

El **técnico líder** de cada atención registra todos los gastos del equipo. El proceso:

1. El **coordinador** hace un **avance de efectivo** al técnico líder antes de la salida
2. El técnico registra cada gasto (transporte, alimentación, hotel, etc.) con evidencia si aplica
3. Cada gasto puede vincularse a un ticket específico
4. En atenciones en ruta: los gastos de ida (primer viaje) se asocian al **primer ticket** del día. Las atenciones consecutivas no generan nuevo viaje de ida
5. Al finalizar el período, el técnico envía la liquidación a **Finanzas** para aprobación
6. Si Finanzas rechaza → el técnico corrige y reenvía. El ciclo continúa hasta aprobación

**Balance final:**

```
Avance de efectivo - Total de gastos = balance
  → balance > 0: saldo a favor de la empresa (el técnico devuelve)
  → balance < 0: saldo a favor del técnico (la empresa paga la diferencia)
```

---

## Entidades y Aggregates

### Aggregate Root — `OperatingExpense`

Liquidación de gastos de un técnico para un período determinado.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| sequence | int | Número secuencial por técnico (auto-generado) |
| employeeId | ULID | Técnico que liquida (Organization BC) |
| serviceZoneId | ULID? | Zona de servicio del técnico (`ClientServiceZone` en Client BC) |
| reason | string | Motivo del período de gastos (ej: "Atención cajeros Zona Norte, semana 20") |
| startDate | date | Inicio del período de liquidación |
| endDate | date | Fin del período de liquidación |
| cashAdvanceId | ULID? | Referencia al adelanto de efectivo recibido |
| status | enum | Estado de la liquidación |
| approvedBy | ULID? | Quién aprobó (usuario de Finanzas) |
| approvedAt | datetime? | Cuándo fue aprobada |
| rejectedBy | ULID? | Quién rechazó |
| rejectedAt | datetime? | Cuándo fue rechazada |
| rejectionReason | string? | Motivo del rechazo |
| deviation | bool | `true` = marcado como desviación por Finanzas |
| deletedAt | datetime? | Soft delete |

#### Enum `ExpenseStatus`

| Valor | Descripción |
|---|---|
| `DRAFT` | En elaboración por el técnico |
| `SUBMITTED` | Enviado a Finanzas para revisión |
| `APPROVED` | Aprobado por Finanzas |
| `REJECTED` | Rechazado por Finanzas (el técnico debe corregir y reenviar) |

#### Ciclo de vida del OperatingExpense

```
DRAFT ──── submit() ────► SUBMITTED ──── approve(approvedBy) ────► APPROVED
  ▲                           │
  │ (técnico corrige)         │ reject(rejectedBy, reason)
  └───────────────────────────┘
                              ▼
                           REJECTED
```

> No hay monto máximo preestablecido para los gastos.

#### Campos calculados (no persistidos)

| Campo | Cálculo |
|---|---|
| `totalTransport` | Suma de ExpenseItems de tipo transporte |
| `totalAccommodation` | Suma de ExpenseItems tipo `HOTEL` |
| `totalFood` | Suma de ExpenseItems tipo alimentación |
| `totalOther` | Suma de ExpenseItems de otros tipos |
| `totalExpenses` | Suma total de todos los ExpenseItems |
| `cashAdvanceAmount` | Monto del `CashAdvance` vinculado |
| `positiveBalance` | `cashAdvanceAmount - totalExpenses` si > 0 (sobra efectivo — devuelve el técnico) |
| `negativeBalance` | `totalExpenses - cashAdvanceAmount` si > 0 (falta efectivo — paga la empresa) |

> `isOutOfZone`: calculado comparando `serviceZoneId` del técnico con la zona del ATM del ticket vinculado.

---

### Entidad — `ExpenseItem`

Ítem individual de gasto dentro de una liquidación.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| expenseId | ULID | Referencia al OperatingExpense |
| type | `ExpenseItemType` | Tipo de gasto (ver enum abajo) |
| amount | decimal | Monto del gasto |
| ticketId | ULID? | Ticket al que se asocia (opcional) |
| notes | string? | Descripción u observaciones |

#### Enum `ExpenseItemType` (tipos reales del sistema operativo)

**Transporte:**
| Valor | Descripción |
|---|---|
| `UBER_EMPRESARIAL` | Uber empresarial (facturado a la empresa) |
| `UBER_EFECTIVO` | Uber pagado en efectivo |
| `TAXI` | Taxi |
| `BUS` | Bus urbano |
| `BUS_INTERPROVINCIAL` | Bus interprovincial |
| `AVION` | Vuelo aéreo |

**Hospedaje:**
| Valor | Descripción |
|---|---|
| `HOTEL` | Hospedaje en hotel |

**Alimentación:**
| Valor | Descripción |
|---|---|
| `ALIMENTACION` | Alimentación |
| `OTROS_ALIMENTACION` | Otros gastos de alimentación |

**Servicios:**
| Valor | Descripción |
|---|---|
| `ENCOMIENDAS` | Envío de encomiendas/paquetería |

**Otros:**
| Valor | Descripción |
|---|---|
| `INDUMENTARIA` | Ropa o equipo de trabajo |
| `INSUMOS` | Materiales o insumos de trabajo |

---

### Entidad — `CashAdvance` (Adelanto de Efectivo)

Adelanto de efectivo que el coordinador entrega al técnico líder antes de su salida.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| advanceNumber | string | Número único del adelanto (auto-generado) |
| employeeId | ULID | Técnico que recibe el adelanto (Organization BC) |
| givenBy | ULID | Usuario que entregó el adelanto (coordinador) |
| amount | decimal | Monto del adelanto |
| givenAt | datetime | Fecha y hora de entrega |
| voucherPath | string? | Ruta al comprobante de pago (evidencia) |
| deletedAt | datetime? | Soft delete |

---

### Entidad — `AfterHourTrip` (Viaje Fuera de Horario)

Registro de atención fuera del horario laboral normal (8:30–17:30). Genera una compensación económica adicional para el técnico.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| employeeId | ULID | Técnico (Organization BC) |
| date | date | Fecha del viaje fuera de horario |
| reason | string | Motivo de la atención fuera de horario |
| additionalPayment | decimal | Compensación económica adicional |
| ticketId | ULID? | Ticket relacionado (si aplica) |
| deletedAt | datetime? | Soft delete |

---

## Reglas de Distribución de Gastos en Rutas

Cuando el técnico atiende múltiples cajeros en ruta:

- **Gastos de ida** (primer viaje del día): se asocian **solo al primer ticket** de la ruta
- **Gastos de desplazamiento entre cajeros**: se asocian al ticket del cajero al que se dirige
- **Gastos de regreso**: se asocian al último ticket del día
- **Gastos compartidos** (ej: alimentación de todo el equipo): el técnico puede distribuirlos manualmente o asociarlos al ticket principal del día

**Ejemplo de liquidación:**

```
Avance: $40
Gastos:
  - Taxi $10 → Ticket #1 (ida)
  - Comida $10 → Ticket #3 (primera atención)
  - Bus $15 → Ticket #5 (segunda atención)
  - Taxi $5 → Ticket #5 (regreso)
Total: $40
Balance: $0 (cuadra exactamente con el avance)
```

---

## Invariantes de Negocio

1. Solo el **técnico líder** (`isInCharge = true` en `TicketTechnician`) registra los gastos del equipo
2. Un `OperatingExpense` solo puede ser enviado a revisión si tiene al menos un `ExpenseItem`
3. Finanzas puede rechazar y pedir corrección las veces que sea necesario
4. `deviation = true` es marcado por Finanzas al aprobar si detecta inconsistencias (no bloquea la aprobación, solo lo señala)
5. Un `CashAdvance` puede vincularse a un solo `OperatingExpense`
6. El `AfterHourTrip` registra cualquier atención que inicie o termine fuera del horario 8:30–17:30

---

## Queries esperadas

- Listar gastos de un técnico con filtro por estado y período de fechas
- Ver detalle de un OperatingExpense (items, balance, tickets vinculados)
- Listar gastos pendientes de aprobación (por Finanzas)
- Dashboard: cuánto lleva gastado cada técnico este mes
- Gastos fuera de zona (por `isOutOfZone`)
- Historial de adelantos de efectivo por coordinador/período
- Total de viajes fuera de horario por técnico/mes

---

## Dependencias cross-BC

| Referencia | BC externo | Descripción |
|---|---|---|
| `OperatingExpense.employeeId` | Organization | Técnico que liquida |
| `OperatingExpense.serviceZoneId` | Client | Zona de servicio del técnico |
| `CashAdvance.givenBy` | Identity | Usuario coordinador que entrega |
| `ExpenseItem.ticketId?` | Support | Ticket al que se asocia el gasto |
| `AfterHourTrip.employeeId` | Organization | Técnico del viaje fuera de horario |
