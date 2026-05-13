# BC Support — Especificación de Dominio

**Propósito:** Gestión del ciclo de vida completo de tickets de servicio técnico de cajeros automáticos.

**Estado:** ⚪ Pendiente. Archivos de entidades vacíos. Segunda prioridad tras Inventory.

**Dependencias cross-BC:** Identity (usuarios creadores), Client (terminales), Inventory (repuestos), Organization (técnicos), Location (ciudades)

---

## Tipos de Servicio

| Tipo | Descripción |
|---|---|
| `PREASLISTAMIENTO` | Cuando se prepara a un terminal antes de la instalación. |
| `INSTALACION` | Instalación de terminales. |
| `DESINSTALACION` | Deinstalación de terminales. |
| `VISITA` | Atenciones técnicas catalogadas como visitas para no caer en multa o por superacion de otras atenciones como por ejemplo correctivo
| `ACTIVACION` | Luego de instalarse puede quedar pendiente la activacion de terminales. |
| `DESACTIVACION` | Desactivacion de terminales. |
| `CORRECTIVO` | El ATM presenta fallas operativas (HW/SW). Cliente solicita revisión y corrección del problema. |
| `MANTENIMIENTO` | Mantenimientos programados o no programados de terminales. |


---

## Entidades y Aggregates

### Aggregate Root — `Ticket`

Intervención técnica solicitada sobre una terminal de un cliente. **Solo empleados internos** crean tickets desde el panel admin.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| ticketNumber | string | Número auto-generado `TK-YYYY-NNNNNN` (único, inmutable) |
| clientId | ULID | Referencia al Client (Client BC) |
| terminalAssignmentId | ULID | Referencia a la unidad física (`TerminalAssignment` en Client BC) |
| problemType | string/enum | Tipo de problema reportado |
| description | string | Detalle del problema |
| serviceType | enum | `CORRECTIVO` / `MANTENIMIENTO` / etc |
| status | `TicketStatus` | Estado actual |
| scheduledAt | datetime? | Fecha programada de atención |
| slaDeadlineAt | datetime? | Límite de cumplimiento SLA (calculado al crear = `scheduledAt + SlaPolicy.maxResponseHours`) |
| inTransitOutAt | datetime? | Timestamp cuando pasa a `IN_TRANSIT_OUT` |
| waitingStartAt | datetime? | Timestamp cuando pasa a `WAITING` |
| assistanceStartAt | datetime? | Timestamp cuando pasa a `IN_ASSISTANCE` |
| inTransitBackAt | datetime? | Timestamp cuando pasa a `IN_TRANSIT_BACK` |
| closedAt | datetime? | Timestamp cuando pasa a `CLOSED` |
| cancelledAt | datetime? | Timestamp cuando pasa a `CANCELLED` |
| createdBy | ULID | Empleado que creó el ticket (User en Identity BC) |
| deletedAt | datetime? | Soft delete |

#### Enum `TicketStatus`

| Valor | Descripción |
|---|---|
| `CREATED` | Ticket creado, sin técnico asignado |
| `ASSIGNED` | Técnico(s) asignado(s) |
| `IN_TRANSIT_OUT` | Técnico en tránsito hacia la terminal (ida) |
| `WAITING` | Técnico en espera en sitio (sin acceso, esperando autorización) |
| `IN_ASSISTANCE` | Técnico realizando la asistencia técnica |
| `PAUSED` | Asistencia pausada temporalmente |
| `IN_TRANSIT_BACK` | Técnico en tránsito de regreso |
| `PENDING_CLOSURE` | Asistencia completada, esperando aprobación del coordinador |
| `CLOSED` | Ticket cerrado y aprobado |
| `CANCELLED` | Ticket cancelado |

#### Ciclo de vida del Ticket

```
CREATED
  │ assign(technicianIds, inChargeId)
  ▼
ASSIGNED
  │ startOutwardJourney()
  ▼
IN_TRANSIT_OUT
  │ arriveAtSite() / startWaiting()
  ▼
WAITING ──── startAssistance() ────►
  (opcional)                        │
                                    ▼
                              IN_ASSISTANCE
                                    │ pause()
                                    ├──────────► PAUSED ──── resume() ──────┐
                                    │                                        │
                                    │◄───────────────────────────────────────┘
                                    │ completeAssistance() + registro diagnóstico
                                    ▼
                            IN_TRANSIT_BACK
                                    │ arriveAtBase()
                                    ▼
                          PENDING_CLOSURE
                                    │ approve(supervisorId)
                                    ▼
                                 CLOSED

CREATED|ASSIGNED|IN_TRANSIT_OUT|WAITING ──── cancel(reason) ────► CANCELLED
```

> Cada transición de estado registra su timestamp (ej: `inTransitOutAt`, `waitingStartAt`, `assistanceStartAt`, `pauseStartAt`, etc.).

#### Invariantes del Ticket

1. `CANCELLED` o `CLOSED` → no puede cambiar de estado
2. Solo usuario con `ticket.approve` puede cerrar desde `PENDING_CLOSURE`
3. `ticketNumber` es único e inmutable desde su creación
4. Requiere registrar diagnóstico (`TicketFinding`) antes de pasar a `IN_TRANSIT_BACK`
5. Cliente y Terminal deben estar activos al crear el ticket
6. El coordinador puede **modificar toda la información** del ticket antes de aprobarlo (incluso después de que el técnico completa la asistencia)
7. El SLA se evalúa como consulta (reportes); no bloquea el flujo operativo

#### Behaviors del Ticket (métodos del Aggregate Root)

| Método | Guards | Efecto |
|---|---|---|
| `create()` | `status = null` → `CREATED`; clientId+terminalAssignmentId activos | Crea ticket, genera `ticketNumber`, emite `TicketCreated` |
| `assign(employeeIds[], inChargeId, scheduledAt)` | `status = CREATED`; al menos un employeeId; inChargeId está en la lista | `status → ASSIGNED`, registra `scheduledAt`, calcula `slaDeadlineAt`, emite `TicketAssigned` |
| `reassign(newEmployeeId, oldEmployeeId)` | `status ≠ CLOSED,CANCELLED` | Crea nuevo `TicketAssignment`, mantiene historial, emite `TicketReassigned` |
| `startOutwardJourney()` | `status = ASSIGNED` | `status → IN_TRANSIT_OUT`, registra `inTransitOutAt` |
| `arriveAtSite()` | `status = IN_TRANSIT_OUT` | `status → IN_ASSISTANCE`, registra `assistanceStartAt` |
| `startWaiting()` | `status = IN_TRANSIT_OUT` | `status → WAITING` |
| `startAssistanceFromWaiting()` | `status = WAITING` | `status → IN_ASSISTANCE`, registra `assistanceStartAt` |
| `pause(reason)` | `status = IN_ASSISTANCE` | `status → PAUSED`, crea `TicketPause` |
| `resume()` | `status = PAUSED` | `status → IN_ASSISTANCE`, cierra `TicketPause.resumedAt` |
| `completeAssistance()` | `status = IN_ASSISTANCE`; existe al menos un `TicketFinding` | `status → IN_TRANSIT_BACK`, registra `inTransitBackAt` |
| `arriveAtBase()` | `status = IN_TRANSIT_BACK` | `status → PENDING_CLOSURE` |
| `approve(approverUserId)` | `status = PENDING_CLOSURE`; approverUserId tiene permiso `ticket.approve` | `status → CLOSED`, registra `closedAt`, genera catCode, emite `TicketClosed` |
| `cancel(reason, cancelledBy)` | `status ∈ {CREATED, ASSIGNED, IN_TRANSIT_OUT, WAITING}`; razón obligatoria | `status → CANCELLED`, registra `cancelledAt`, emite `TicketCancelled` |
| `reschedule(newDate, reason, rescheduledBy)` | `status ∈ {CREATED, ASSIGNED}`; `newDate > now()` | Actualiza `scheduledAt`, recalcula `slaDeadlineAt`, crea `TicketReschedule`, emite `TicketRescheduled` |

#### Documento de Asistencia Técnica (CAT)

Al cerrar y aprobar un ticket, el sistema genera el **Certificado de Asistencia Técnica (CAT)**:

- **`catCode` (sistema)**: Código único generado automáticamente al aprobarse el ticket. Identifica inequívocamente la atención en el sistema. Patrón sugerido: `{clientCode}-{cityCode}-{terminalCode}-{YYYYMMDD}-{seq}` (ej: `CLI01-GYE-ATM042-20250510-003`)
- **`physicalDocNumber` (documento físico)**: Número del documento físico firmado. Lo ingresa manualmente el coordinador. Opcional — el documento físico puede tener su propia numeración independiente.
- Contiene: datos del cliente, equipo, ciudad, tiempos de la atención, tabla de partes (seriales entrada/salida), observaciones, firmas
- **Se genera 1 CAT por ATM atendido** — incluso en atenciones en ruta (múltiples cajeros), cada cajero tiene su propio CAT
- El cliente puede descargarlo desde el portal una vez que el ticket está `CLOSED`
- Tanto CMS como el cliente archivan el documento

---

### Entidad — `TicketAssigment` (Asignación de Técnicos)

Un ticket puede tener **múltiples técnicos** asignados. Uno es el técnico **líder** (encargado principal): él llena los reportes y registra los gastos.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| ticketId | ULID | Referencia al Ticket |
| employeeId | ULID | Técnico asignado (Organization BC) |
| isInCharge | bool | `true` = técnico líder/encargado principal |
| assignedAt | datetime | Cuándo fue asignado |
| reassignedFrom | ULID? | Si es reasignación, referencia al técnico anterior |

**Reglas de asignación:**

- Se valida por **rol** del usuario (no por perfil de empleado): el empleado asignado debe tener un `userId` activo con rol `TECHNICIAN` (o similar)
- El empleado **no debe tener status `ON_VACATION`** ni un `EmployeeSpecialPeriod` activo al momento de la asignación
- **Un técnico SÍ puede asignarse aunque tenga tickets en curso** — en la práctica, el coordinador puede asignarle un ticket cercano mientras está en otra atención (flexibilidad operativa)
- `standby` = técnico trabajando en fin de semana o feriado → no bloquea la asignación
- La validación es en **Application Layer** consultando Organization BC y Identity BC por sus ULIDs

**Invariantes:**

1. Solo puede haber **un** técnico con `isInCharge = true` por ticket
2. Los técnicos adicionales son de entrenamiento o apoyo
3. La reasignación crea un nuevo registro — el historial se conserva

---

### Entidad — `Journey` (Registro de Viaje) ⚪ Nuevo — diseño definitivo

Cada atención implica un viaje. El `Journey` registra cada tramo del desplazamiento del técnico.

**Atención en ruta:** si el técnico atiende cajero A y luego cajero B en la misma jornada, solo se registra **un viaje de ida** en el primer ticket. El ticket B no tiene viaje de ida propio. Solo al finalizar todos los cajeros del día se registra el viaje de regreso.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| ticketId | ULID | Ticket al que pertenece este tramo |
| type | enum | `OUTWARD` / `RETURN` / `NEXT_SITE` |
| startedAt | datetime | Inicio del viaje |
| arrivedAt | datetime? | Llegada al destino |
| isPartOfRoute | bool | `true` si es parte de una ruta (atención en ruta) |
| notes | string? | Observaciones |

**Enum JourneyType:**

| Valor | Descripción |
|---|---|
| `OUTWARD` | Viaje de ida desde oficina/base hacia el primer cajero del día |
| `NEXT_SITE` | Desplazamiento entre cajeros en una ruta (no regresa a base) |
| `RETURN` | Viaje de regreso a la base al finalizar el día |

> Solo el ticket **primero de la ruta** puede tener un `Journey` de tipo `OUTWARD`. Los tickets siguientes de la ruta tienen tipo `NEXT_SITE` si es desplazamiento entre cajeros.

---

### Entidad — `TicketFinding` (Diagnóstico del Técnico)

Registro estructurado del diagnóstico al finalizar la asistencia. **Obligatorio** para poder cerrar el ticket. Solo puede existir **un** `TicketFinding` por ticket.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| ticketId | ULID | Referencia al Ticket |
| catCode | string? | Código único del CAT generado por el sistema (se asigna al aprobar el ticket) |
| physicalDocNumber | string? | Número del documento físico CAT firmado (entrada manual, opcional) |
| problemTypeFound | string/enum | Tipo de problema encontrado |
| problemDetail | string | Descripción técnica |
| batteryVoltageOk | bool | Verificación de voltaje de batería |
| solution | string | Solución aplicada |
| registeredBy | ULID | Técnico que registró (User en Identity BC) |

---

### Entidad — `TicketSparePart` (Repuestos en el Ticket)

Registro de cada repuesto que entra o sale del cajero durante la asistencia. Se registra en el `TicketFinding`.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| ticketId | ULID | Referencia al Ticket |
| findingId | ULID | Referencia al TicketFinding |
| productCode | string | Código del repuesto (Inventory BC) |
| serialNumber | string? | Serial de la unidad (si aplica) |
| quantity | int | Cantidad (siempre 1 si tiene serial) |
| isIncoming | bool | `true` = entra al cajero (repuesto nuevo); `false` = sale del cajero (dañado) |
| isVisibleToClient | bool | `true` = visible en el CAT del cliente; `false` = solo registro interno (partes "ocultas") |
| autoMarkedDamaged | bool | `true` = sistema marcó automáticamente el stock como DAMAGED al registrar la salida |

> **Partes "internas"**: algunos repuestos no aparecen en el documento del cliente (para no perder facturación de atenciones específicas). El coordinador controla la visibilidad campo a campo.

---

### Entidad — `TicketPendingSparePart` (Repuesto Pendiente)

Repuesto que se necesitó durante la asistencia pero no estaba disponible. Genera una solicitud posterior.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| ticketId | ULID | Referencia al Ticket |
| productCode | string | Código del repuesto requerido |
| quantity | int | Cantidad requerida |
| notes | string? | Motivo o descripción |

---

### Entidad — `TicketEvidence` (Fotos/Evidencias)

Archivos de evidencia fotográfica adjuntos al ticket.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| ticketId | ULID | Referencia al Ticket |
| filePath | string | Ruta al archivo almacenado |
| uploadedBy | ULID | Quién subió (User en Identity BC) |
| uploadedAt | datetime | Fecha de subida |

---

### Entidad — `TicketPause` (Pausa de Asistencia)

Registro de cada pausa durante la asistencia técnica.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| ticketId | ULID | Referencia al Ticket |
| pauseReason | string | Motivo de la pausa |
| pausedAt | datetime | Inicio de la pausa |
| resumedAt | datetime? | Fin de la pausa (`null` si sigue pausado) |

---

### Entidad — `TicketReschedule` (Reprogramación)

Historial de reprogramaciones del ticket. Puede ser por solicitud del cliente o por decisión interna de CMS.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| ticketId | ULID | Referencia al Ticket |
| previousScheduledAt | datetime | Fecha programada **antes** de la reprogramación |
| newScheduledAt | datetime | Nueva fecha programada |
| reason | string | Motivo de la reprogramación |
| requestedBy | enum | `CLIENT` / `CMS` — quién solicitó la reprogramación |
| rescheduledBy | ULID | Usuario que registró el cambio (User en Identity BC) |
| rescheduledAt | datetime | Cuándo se registró |

> Si la reprogramación implica cambio de técnico, se crea también un nuevo `TicketAssignment` con `reassignedFrom` al técnico anterior y se notifica al técnico saliente y entrante.

---

## Integración con Inventory BC

Al registrar el `TicketFinding`, el técnico registra los repuestos usados:

- **Repuesto nuevo** que entra al cajero: `isIncoming = true` → genera `Movement(EXIT)` del stock de la mochila del técnico
- **Repuesto dañado** que sale del cajero: `isIncoming = false` → `SerializedStock.markDamaged()` automático en el stock del técnico
- El técnico **no puede enviar partes dañadas directamente al laboratorio** — primero las envía a la bodega principal vía `Movement(TRANSFER)`, y desde allí el laboratorista las recibe y decide: reparar o dar de baja
- **Casos de emergencia**: si el técnico usó repuestos sin solicitud formal previa, puede registrar los repuestos directamente en el ticket — el sistema los descuenta del stock sin requerir un `Request` previo

## Integración con Expense BC

Los gastos operativos se vinculan al ticket mediante `ticketId` en `ExpenseItem` del BC Expense. Esta referencia es opcional — puede existir un gasto sin ticket asociado.

---

## SLA — Políticas de Nivel de Servicio

La **política SLA** está definida en **Client BC** (`SlaPolicy`) como parámetros configurables por cliente y tipo de servicio. Support BC la **consume** al crear el ticket:

- Al asignar el ticket: `slaDeadlineAt = scheduledAt + SlaPolicy.maxResponseHours`
- Al cerrar: se compara `closedAt` con `slaDeadlineAt` → determina si se cumplió o no
- El incumplimiento SLA **no bloquea** el flujo operativo — se registra como dato para reportes y para que el coordinador sea notificado a tiempo
- Support BC consulta `SlaPolicy` de Client BC **solo por query** (ULID del cliente) — no instancia entidades del Client BC

**Notificación preventiva:** cuando `now() >= slaDeadlineAt - threshold` → emite `SlaAboutToExpire` event → Notification BC notifica al coordinador.

---

## Eventos de Dominio

| Evento | Emisor | Descripción |
|---|---|---|
| `TicketCreated` | Ticket.create() | Ticket registrado en el sistema |
| `TicketAssigned` | Ticket.assign() | Técnico(s) asignado(s), fecha programada registrada |
| `TicketReassigned` | Ticket.reassign() | Técnico cambiado, historial conservado |
| `TicketRescheduled` | Ticket.reschedule() | Fecha reprogramada (cliente o CMS) |
| `TicketCancelled` | Ticket.cancel() | Ticket cancelado |
| `TicketClosed` | Ticket.approve() | Ticket cerrado y aprobado por coordinador |
| `SlaAboutToExpire` | Infrastructure (scheduler) | SLA próximo a incumplirse — alerta al coordinador |

---

## Notificaciones

Todas las notificaciones se envían vía Notification BC (Domain Events):

| Evento | Destinatario | Canal |
|---|---|---|
| `TicketCreated` | Técnico(s) asignado(s) | Email + WhatsApp |
| `TicketReassigned` | Técnico saliente + entrante | Email + WhatsApp |
| `TicketRescheduled` | Técnico(s) + Contacto del cliente | Email + WhatsApp |
| `TicketCancelled` | Técnico(s) + Contacto del cliente | Email + WhatsApp |
| `TicketClosed` | Contacto del cliente | Email |
| `SlaAboutToExpire` | Coordinador | Email |

---

## Queries esperadas

- Listar tickets por cliente, técnico, estado, rango de fechas
- Ver detalle completo de un ticket (técnicos, diagnóstico, evidencias, pausas, repuestos, reprogramaciones)
- Listar tickets pendientes de aprobación (coordinador)
- Listar tickets asignados a un técnico específico
- Tiempo promedio de atención por cliente / técnico / zona
- Tickets atendidos fuera de zona del técnico responsable
- Dashboard: cuántos tickets abiertos hoy, técnicos disponibles/ocupados
- Reporte de cumplimiento SLA por cliente / período
- Historial de reprogramaciones por ticket

---

## Dependencias cross-BC

| Referencia | BC externo | Descripción |
|---|---|---|
| `Ticket.clientId` | Client | Cliente del ticket |
| `Ticket.terminalAssignmentId` | Client | Terminal física del ticket |
| `Ticket.createdBy` | Identity | Usuario que creó el ticket |
| `TicketTechnician.employeeId` | Organization | Técnico asignado |
| `TicketFinding.registeredBy` | Identity | Técnico que registró el diagnóstico |
| `TicketSparePart.productCode` | Inventory | Producto del repuesto |
| `Journey` | Expense | Gastos de viaje vinculados al ticket |
