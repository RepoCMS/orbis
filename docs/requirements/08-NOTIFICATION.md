# BC Notification — Especificación de Dominio

**Propósito:** Centralizar el envío de notificaciones por múltiples canales (email, WhatsApp). Gestiona preferencias, plantillas, historial y reintentos. BC transversal.

**Estado:** ⚪ Pendiente. Sin código. Baja prioridad MVP.

**Dependencias cross-BC:** Identity (usuarios), Organization (empleados), Client (contactos)

---

## Canales de Notificación

| Canal | Estado | Descripción |
|---|---|---|
| `EMAIL` | Prioridad 1 | Email transaccional |
| `WHATSAPP` | Prioridad 2 | WhatsApp Business API |
| `SMS` | Futuro | SMS (extensible) |
| `PUSH` | Futuro | Push notification app móvil (extensible) |

---

## Entidades

### `NotificationPreference`

Preferencias de canal por destinatario. Un destinatario puede tener múltiples preferencias (una por canal).

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| recipientType | enum | `EMPLOYEE` / `CONTACT` |
| recipientId | ULID | ID del destinatario (Employee o Contact) |
| channel | enum | Canal de notificación (`EMAIL`, `WHATSAPP`, etc.) |
| isEnabled | bool | `true` = canal habilitado para este destinatario |
| contactValue | string | Email o número de WhatsApp (según el canal) |

> Por defecto, los canales están **deshabilitados**. El administrador o el propio usuario los activa.

---

### `NotificationTemplate`

Plantilla parametrizable para un tipo de evento y canal. Soporta variables dinámicas.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| eventType | enum | Tipo de evento que la dispara (ver lista abajo) |
| channel | enum | Canal al que aplica |
| subject | string? | Asunto (para email) |
| bodyTemplate | string | Cuerpo con variables (ej: `Hola {{employeeName}}, el ticket {{ticketNumber}}...`) |
| isActive | bool | Si la plantilla está activa |

---

### `NotificationLog`

Registro inmutable de cada notificación enviada.

| Campo | Tipo | Descripción |
|---|---|---|
| id | ULID | Identificador único |
| recipientType | enum | `EMPLOYEE` / `CONTACT` |
| recipientId | ULID | ID del destinatario |
| channel | enum | Canal usado |
| eventType | enum | Evento que originó la notificación |
| subject | string? | Asunto enviado |
| body | string | Contenido enviado |
| status | enum | `SENT` / `FAILED` / `PENDING` |
| attemptCount | int | Número de intentos |
| lastAttemptAt | datetime | Fecha del último intento |
| sentAt | datetime? | Fecha de envío exitoso |
| failureReason | string? | Motivo del fallo (si aplica) |
| referenceId | ULID? | ID de la entidad relacionada (ej: ticketId, expenseId) |

---

## Eventos que Disparan Notificaciones

| Evento | Destinatario | Canal |
|---|---|---|
| Ticket creado | Técnico(s) asignado(s) | Email + WhatsApp |
| Ticket asignado a técnico | Técnico asignado | Email + WhatsApp |
| Ticket estado cambia | Contacto del cliente (si configurado) | Email |
| Ticket aprobado/cerrado | Contacto del cliente | Email |
| Ticket cancelado | Técnico(s) + Contacto cliente | Email |
| Garantía próxima a vencer (threshold days) | Coordinador + Contacto cliente | Email |
| SLA próximo a incumplirse | Coordinador | Email |
| Gasto operativo rechazado | Técnico | Email + WhatsApp |
| Solicitud de inventario aprobada | Técnico | Email + WhatsApp |
| Solicitud pendiente de aceptación | Técnico | Email + WhatsApp |
| Activación de cuenta (nuevo usuario) | Usuario (Employee/Contact) | Email |
| Contraseña temporal (desbloqueo) | Usuario (Employee/Contact) | Email |

---

## Mecanismo de Integración

Los otros BCs **no conocen** el BC Notification. Se integran via:

1. **Domain Events** (preferido): el handler de Application publica un evento (`TicketApproved`, `ExpenseRejected`, etc.). Un listener de Infrastructure del BC Notification reacciona y encola la notificación.
2. **Application Service** de Notification (alternativa): otro BC invoca `SendNotificationCommand` directamente si necesita control fino sobre el contenido.

---

## Invariantes de Negocio

1. Si el destinatario no tiene preferencia habilitada para el canal → no se envía (no es un error)
2. El `NotificationLog` se conserva indefinidamente para auditoría
3. Los reintentos se limitan a **3 intentos** con backoff exponencial
4. Las notificaciones de seguridad (activación, contraseña) se envían siempre por email, independientemente de las preferencias de canal

---

## Queries esperadas

- Listar historial de notificaciones por destinatario
- Ver notificaciones fallidas pendientes de reintento
- Listar preferencias de un destinatario
