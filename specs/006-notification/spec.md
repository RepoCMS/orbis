# Feature Specification: Notification

**Spec path**: `specs/006-notification/spec.md`
**Created**: 2026-06-17
**Last updated**: 2026-08-07 (T01 — verificado contra código real con CodeGraph + `bin/wiring-check.php`)
**Status**: In Development (~75% implementado)
**Previous status**: ~60% (spec esqueleto de 65 líneas, desactualizado — describía el BC como "sin ORM mappings ni integración real", lo cual es FALSO)

---

## 1. Descripción General

El Bounded Context **Notification** gestiona el envío de notificaciones a través de múltiples canales con arquitectura fire-and-forget. Está compuesto por 96 archivos PHP:

- **Dominio completo**: 3 entidades, 4 enums, 3 value objects, 3 interfaces de repositorio, 2 servicios de dominio, 8 excepciones.
- **Aplicación completa**: 8 Commands + Handlers, 1 Message, 5 Queries + Handlers, 4 Response DTOs, 2 servicios (TemplateVariableResolver, SecurityUserResolverInterface), 6 excepciones.
- **Infraestructura completa**: 3 repositorios Doctrine + 3 mappings XML + 7 DBAL types, 4 channel senders, 2 comandos de consola, 1 EventListener, 1 Messenger handler cross-BC (107 eventos), NotificationDispatcher, 5 contact resolvers, SecurityUserResolver, NotificationTemplateRenderer.
- **Presentación completa**: 8 controllers HTTP (4 API + 4 web), templates Twig (`templates/notification/`), Stimulus `notification_badge_controller.js`.

El gap principal NO es la estructura (esqueleto) sino la **cadena funcional cross-BC**: los 107 eventos de negocio están mapeados en `CrossBcDomainEventHandler`, pero solo 68 tienen destinatarios resueltos y solo 53 tienen plantillas seedeadas → ~50% de los eventos producen una notificación efectiva.

---

## 2. Business Rules

| # | Regla | Estado |
|---|-------|--------|
| NO‑01 | **Fire-and-forget**: las notificaciones se envían sin esperar confirmación (async) | ✅ Implementado |
| NO‑02 | **Retención 3 meses**: logs se purgan con `purgeOlderThan(-3 months)` | ✅ Implementado |
| NO‑03 | **Canales**: In-App, Email, SMS, WhatsApp. MVP: SMS y WhatsApp solo log | ✅ Implementado (Email real vía Symfony Mailer; SMS/WhatsApp stubs) |
| NO‑04 | **Contactos resueltos desde BC origen** vía tagged resolvers (User, Contact, Employee, Location) | ✅ Implementado |
| NO‑05 | **Seguridad**: eventos de seguridad (activación de cuenta, reset de password) se envían SOLO por email, sin pasar por preferencias | ✅ Implementado (`NotificationDispatcher::SECURITY_EVENTS`) |
| NO‑06 | **Preferencias**: por destinatario + canal + evento; in-app no desactivable | ✅ Implementado (`CannotDisableInAppException`) |
| NO‑07 | **Sin plantilla → sin envío**: si no existe `NotificationTemplate` para (eventType, channel), la notificación NO se despacha (`continue` en dispatcher) | ⚠️ Implementado pero es la causa del gap de cobertura (54/107 eventos) |
| NO‑08 | **Retry**: solo desde estado FAILED; resetea a PENDING | ⚠️ Implementado pero NO re-despacha el mensaje → el retry manual no reenvía |
| NO‑09 | **Intentos**: máx. 3 intentos por log (`attemptCount >= 3` → FAILED) | ✅ Implementado |

---

## 3. Entities

| Entidad | Descripción | Estado |
|---------|-------------|--------|
| `NotificationLog` | Log de envío. Máquina de estados PENDING → SENT / FAILED (3 intentos máx.), `markAsRead()` solo IN_APP, `readAt`, `referenceType`/`referenceId` para trazabilidad del recurso origen | ✅ Implementado (entity + `.orm.xml` tabla `notification_logs`) |
| `NotificationTemplate` | Plantilla por (eventType, channel): subject + bodyTemplate con variables `{{var}}`, `isActive` | ✅ Implementado (entity + `.orm.xml` tabla `notification_templates`) |
| `NotificationPreference` | Preferencia por (recipientType, recipientId, channel, eventType) con `isEnabled`; canal IN_APP no desactivable | ✅ Implementado (entity + `.orm.xml` tabla `notification_preferences`) |

**Mappings**: `NotificationLog.orm.xml`, `NotificationTemplate.orm.xml`, `NotificationPreference.orm.xml` — tablas **sin prefijo** `orbis_` (discrepancia con ADR-013, ver §9).

**⚠️ Sin migraciones**: ninguna de las 30 migraciones del proyecto crea `notification_logs`, `notification_templates` ni `notification_preferences`. El schema del BC NO está versionado.

---

## 4. Enums, Value Objects y Domain Services

| Tipo | Nombre | Detalle |
|------|--------|---------|
| Enum | `EventType` | **108 cases** (eventos de negocio de los BCs origen: Support tickets, Expense, Identity, Inventory stock/requests, Organization empleados/vacaciones/sobretiempo/zonas/períodos/ciudades, Client). 107 están mapeados en el handler cross-BC; `WARRANTY_EXPIRING_SOON` es un case **huérfano** (tiene plantilla en el seed pero ningún evento real lo dispara) |
| Enum | `NotificationChannel` | `in_app`, `email`, `whatsapp`, `sms` |
| Enum | `NotificationStatus` | `pending`, `sent`, `failed` |
| Enum | `RecipientType` | `employee`, `contact`, `user`, `location` |
| VO | `NotificationLogIdValueObject`, `NotificationPreferenceIdValueObject`, `NotificationTemplateIdValueObject` | ULID 26 chars, custom DBAL types registrados |
| Domain Service | `ChannelSenderInterface` | Puerto de envío por canal (4 implementaciones) |
| Domain Service | `ContactResolverInterface` | Puerto de resolución de contactos (tagged resolvers) |

---

## 5. Commands (8 + 1 Message)

| Comando | Descripción | Estado |
|---------|-------------|--------|
| `SendNotification` | Crea `NotificationLog` (PENDING) y despacha `SendNotificationMessage` a `async_notifications` | ✅ Implementado |
| `SendNotificationMessage` (Message async) | Ejecuta el sender del canal, `markSent()` / `markFailed()` | ✅ Implementado |
| `CreateNotificationTemplate` | Crea plantilla (valida duplicados eventType+channel, vars permitidas) | ✅ Implementado |
| `UpdateNotificationTemplate` | Actualiza plantilla | ✅ Implementado |
| `MarkNotificationRead` | Marca leída (solo IN_APP) | ✅ Implementado |
| `MarkAllNotificationsRead` | Marca todas las de un employeeId | ✅ Implementado |
| `RetryNotification` | Solo FAILED → `retry()` (PENDING, intentos 0) | ⚠️ **No re-despacha el mensaje de envío** (gap) |
| `UpdateNotificationPreference` | Actualiza preferencia | ✅ Implementado |
| `CleanupOldNotifications` | Purgar logs > 3 meses (`purgeOlderThan`) | ✅ Implementado |

---

## 6. Queries (5 + 4 Responses)

| Query | Descripción | Estado |
|-------|-------------|--------|
| `ListInboxNotifications` | Inbox por employeeId (scope), con paginación | ✅ Implementado |
| `CountUnreadNotifications` | Conteo no leídas (badge) | ✅ Implementado |
| `ListNotificationLogs` | Historial admin (scope por permisos) | ✅ Implementado |
| `ListNotificationTemplates` | Lista plantillas admin | ✅ Implementado |
| `ListNotificationPreferences` | Lista preferencias admin | ✅ Implementado |
| Responses | `InboxNotificationResponse`, `NotificationLogResponse`, `NotificationTemplateResponse`, `NotificationPreferenceResponse` | ✅ Implementado |

---

## 7. Cross-BC (el corazón del BC)

### 7.1 Flujo

```
Domain Entity (BC A) → domainEvent → SymfonyDomainEventPublisher (Shared)
  → DomainEventBridgeSubscriber (Shared) → DomainEventMessage → transporte async_events
  → CrossBcDomainEventHandler (Notification, 107 métodos on*)
  → NotificationDispatcher::dispatchForEvent(eventType, recipients, subject, bodyTemplate, vars, reference)
    → por cada canal: template en BD? (NO → continue) → preferencias (NO para security)
    → contact resolver (solo EMAIL/WHATSAPP/SMS) → TemplateVariableResolver.render
    → SendNotificationCommand (sync) → SendNotificationHandler → NotificationLog + SendNotificationMessage (async_notifications)
    → SendNotificationMessageHandler (EventListener) → ChannelSender → markSent/markFailed
```

### 7.2 Cobertura de eventos (verificado en código)

| Métrica | Conteo | % |
|---------|--------|---|
| Eventos mapeados en `CrossBcDomainEventHandler` (match en `__invoke`) | **107** | 100% |
| Con destinatarios resueltos (`recipients` no vacío) | **68** | 64% |
| Sin destinatarios (`recipients: []` → NO se genera nada) | **39** | 36% |
| Con plantillas seedeadas entre los manejados (catálogo `SeedNotificationTemplatesCommand`) | **53** (94 entradas) | 49.5% |
| Sin plantilla → dispatcher hace `continue` → no se envía | **54** | 50.5% |
| Eventos con test en `CrossBcDomainEventHandlerTest` | **29** | 27% |

**Eventos SIN destinatario (39)** — requieren resolver destinatario en BC origen o ampliar el evento:
`CashAdvanceUpdated`, `CityTransferred`, `ClientCreated`, `ClientSaleOrderCompleted`, `ContactAdded`, `ContactUserUnlinked`, `EntryConfirmed`, `PartRepaired` (TODO explícito), `ProductCreated`, `RequestApproved`, `RoleCreated`, `RoleDeleted`, `RolePermissionsUpdated`, `SerializedStockCreated`, `ServiceZoneCreated/Deactivated/Reactivated`, `SlaPolicyCreated`, `SpecialPeriodEdited`, `StockMoved`, `TerminalRegistered/Updated/Deactivated/Reactivated`, `TerminalAssignmentRegistered/Updated/Deactivated/Reactivated`, `TicketArrivedAtSite`, `TicketAssistanceStarted/Completed`, `TicketFindingRegistered`, `TicketJourneyStarted`, `TicketPaused/Resumed/WaitingStarted`, `WarrantyRegistered/Updated/Expired`, `ContractCreated/Updated/Cancelled` (parcial, ver nota).

> Nota: algunos son intencionales (eventos de tracking sin destinatario definido: `TicketJourneyStarted`, `StockMoved`, `SerializedStockCreated`), pero la mayoría son gaps reales (`WarrantyExpired` → cliente, `RequestApproved` → técnico solicitante, `PartRepaired` → técnico responsable, `ContractCancelled` → cliente).

**Eventos SIN plantilla en el seed (54 de 107 manejados)** — aun con destinatario, no se envían:
`AdminSeeded`, `CityTransferred`, `ClientSaleOrderCompleted`, `ClientSaleOrderCreated`, `ClientUpdated`, `ContactAccessProvisioned`, `ContactDeactivated`, `ContactLinked`, `ContactReactivated`, `ContactUpdated`, `ContactUserLinked`, `ContactUserUnlinked`, `ContractCancelled`, `ContractCreated`, `ContractUpdated`, `EmployeeAccessProvisioned`, `EmployeeDeleted`, `EmployeeReactivated`, `EmployeeRegistered`, `EmployeeUserLinked`, `EmployeeUserUnlinked`, `MfaDisabledByAdmin`, `MfaSetupCompleted`, `OvertimeApproved`, `OvertimeCorrected`, `OvertimeRegistered`, `OvertimeRejected`, `OvertimeReviewRequested`, `PasswordChanged`, `PasswordResetRequested`, `RoleCreated`, `RoleDeleted`, `RolePermissionsUpdated`, `ServiceZoneCreated`, `ServiceZoneDeactivated`, `ServiceZoneReactivated`, `SlaPolicyCreated`, `SpecialPeriodCancelled`, `SpecialPeriodCompleted`, `SpecialPeriodEdited`, `TerminalAssignmentDeactivated`, `TerminalAssignmentReactivated`, `TerminalAssignmentRegistered`, `TerminalAssignmentUpdated`, `TerminalDeactivated`, `TerminalReactivated`, `TerminalRegistered`, `TerminalUpdated`, `UserUnlocked`, `VacationCancelled`, `VacationRejected`, `WarrantyExpired`, `WarrantyRegistered`, `WarrantyUpdated`.

> Nota: varios eventos tienen plantilla SOLO para IN_APP (ej. `CashAdvanceUpdated`, `ContactAdded`, `ClientCreated`, `PartRepaired`, `TicketReopened`, `TicketFindingRegistered`, `EmployeeDeactivated`, `SerializedStockCreated`, `ProductCreated`) → se envían in-app pero no por email/WhatsApp.

### 7.3 Contact Resolvers (tagged `app.notification_contact_resolver`)

| Resolver | RecipientType | BC origen | Estado |
|----------|---------------|-----------|--------|
| `UserContactResolver` | `user` | Identity (User) + Organization (Employee.userId) + Client (Contact.userId) — fallback username | ✅ |
| `ClientContactResolver` | `contact` | Client (Contact) | ✅ |
| `OrganizationContactResolver` | `employee` | Organization (Employee) | ✅ |
| `LocationContactResolver` | `location` | Inventory (Location.employeeId) → Organization (Employee) | ✅ |
| `ChainContactResolver` | — | Orquesta los 4 con `!tagged_iterator` | ✅ |

**⚠️ TODO obsoleto en código**: `onStockBelowThreshold()` contiene un comentario "Se necesita un LocationContactResolver" pero el resolver YA existe y está wired desde 2026-07. Limpiar el TODO.

### 7.4 BCs origen consumidos

| BC | Eventos mapeados | Contactos vía |
|----|------------------|---------------|
| **Client** | 29 (clientes, contactos, terminales, asignaciones, contratos, garantías, SLA policy) | ClientContactResolver / UserContactResolver |
| **Organization** | 24 (empleados, vacaciones, sobretiempo, zonas, períodos especiales, ciudades) | OrganizationContactResolver |
| **Support** | 18 (tickets + SLA) | UserContactResolver / OrganizationContactResolver |
| **Identity** | 14 (seguridad, roles, MFA, registros) | UserContactResolver |
| **Inventory** | 13 (stock, solicitudes de repuesto, productos, entradas) | LocationContactResolver / OrganizationContactResolver |
| **Expense** | 9 (liquidaciones, adelantos) | OrganizationContactResolver |

---

## 8. Channels (4)

| Canal | Clase | Estado |
|-------|-------|--------|
| **Email** | `EmailChannelSender` | ✅ **Real** — Symfony Mailer + `MAILER_FROM_EMAIL`, resuelve contacto y lanza si no hay email |
| **In-App** | `InAppChannelSender` | ✅ Implementado (el log en BD es el "delivery"; inbox lo consume) |
| **SMS** | `SmsChannelSender` | ⚠️ Stub — solo `LoggerInterface::info`, sin provider |
| **WhatsApp** | `WhatsAppChannelSender` | ⚠️ Stub — solo log (decisión MVP vigente; Twilio/WATI para producción) |

`SendNotificationMessageHandler` inyecta los 4 senders, ejecuta en `try/catch` y actualiza el log (`markSent`/`markFailed`); reintentos del transporte `async_notifications`: 5 con backoff 10s ×3 (máx. 1h).

---

## 9. Presentation & UI

| Controller | Ruta | Permiso |
|------------|------|---------|
| `ApiNotificationInboxController` | `/api/v1/notifications/inbox` (+ unread-count, read, read-all) | IS_AUTHENTICATED |
| `ApiNotificationLogController` | `/api/v1/notifications/logs` (+ retry) | notification.admin |
| `ApiNotificationTemplateController` | `/api/v1/notifications/templates` (list, create, update) | notification.admin |
| `ApiNotificationPreferenceController` | `/api/v1/notifications/preferences` (list, update) | notification.admin |
| `WebNotificationController` | `/notifications/inbox`, `/badge`, `/admin`, `/admin/templates/{id}/update`, `/admin/logs/{id}/retry` | IS_AUTHENTICATED / notification.admin |
| `NotificationLogController` | `/notifications/logs` (list, show, retry) | notification.admin |
| `NotificationTemplateController` | `/notifications/templates` (list, new, create, show, edit, update) | notification.admin |
| `NotificationPreferenceController` | `/notifications/preferences` (list, toggle) | notification.admin |

Frontend: templates Twig `templates/notification/` (admin.html.twig con tabs Plantillas/Historial + KPIs, inbox, etc.) y Stimulus `notification_badge_controller.js` (polling del badge).

---

## 10. Wiring (verificado: `php bin/wiring-check.php` → 346 targets, **0 huérfanos**)

### Commands

| Command | Caller real |
|---------|-------------|
| `SendNotificationCommand` | `NotificationDispatcher::dispatchForRecipient()` (`src/app/Notification/Infrastructure/Service/NotificationDispatcher.php:113` — vía MessageBus desde `CrossBcDomainEventHandler`) |
| `CreateNotificationTemplateCommand` | `ApiNotificationTemplateController:65` (POST `/api/v1/notifications/templates`), `NotificationTemplateController:95` (POST `/notifications/templates/new`) |
| `UpdateNotificationTemplateCommand` | `ApiNotificationTemplateController:100` (PUT), `NotificationTemplateController:207` (POST/PUT), `WebNotificationController:211` (POST `/admin/templates/{id}/update`) |
| `MarkNotificationReadCommand` | `ApiNotificationInboxController:74` (POST `/inbox/{id}/read`), `WebNotificationController:88` (POST `/inbox/{id}/read`) |
| `MarkAllNotificationsReadCommand` | `ApiNotificationInboxController:92` (POST `/inbox/read-all`), `WebNotificationController:117` (POST `/inbox/read-all`) |
| `RetryNotificationCommand` | `ApiNotificationLogController:49` (POST `/logs/{id}/retry`), `NotificationLogController:97`, `WebNotificationController:238` (POST `/admin/logs/{id}/retry`) |
| `UpdateNotificationPreferenceCommand` | `ApiNotificationPreferenceController:59` (PATCH `/preferences/{id}`), `NotificationPreferenceController:71` (POST `/preferences/{id}/toggle`) |
| `CleanupOldNotificationsCommand` | `CleanupNotificationsCommand` (console `orbis:notifications:cleanup`, `CleanupNotificationsCommand.php:31`) — requiere cron |

### Messages

| Message | Caller | Transporte |
|---------|--------|-----------|
| `SendNotificationMessage` | `SendNotificationHandler:43` (tras crear el log) | `async_notifications` (5 retries) |
| `DomainEventMessage` (cross-BC) | `DomainEventBridgeSubscriber` (Shared Kernel) publica todos los domain events | `async_events` (3 retries) |

### Queries

| Query | Caller real |
|-------|-------------|
| `ListInboxNotificationsQuery` | `ApiNotificationInboxController:37`, `WebNotificationController:54` |
| `CountUnreadNotificationsQuery` | `ApiNotificationInboxController:57`, `WebNotificationController:61`, `WebNotificationController:141` (badge) |
| `ListNotificationLogsQuery` | `ApiNotificationLogController:28`, `NotificationLogController:34`, `NotificationLogController:67`, `WebNotificationController:169` (admin) |
| `ListNotificationTemplatesQuery` | `ApiNotificationTemplateController:31`, `NotificationTemplateController:31,127,155,189`, `WebNotificationController:163` (admin) |
| `ListNotificationPreferencesQuery` | `ApiNotificationPreferenceController:34`, `NotificationPreferenceController:44` |

### Handlers / Subscribers

| Handler | Disparado por |
|---------|---------------|
| `CrossBcDomainEventHandler` | 107 eventos de negocio vía `DomainEventMessage` (async_events) — `#[AsMessageHandler]` |
| `SendNotificationMessageHandler` | `SendNotificationMessage` (async_notifications) — `#[AsMessageHandler]` |
| `ContactResolverInterface` → `ChainContactResolver` | `NotificationDispatcher`, `TemplateVariableResolver`, `EmailChannelSender` (DI) |

### Servicios de aplicación registrados en `services.yaml`

| Interface | Implementación |
|-----------|----------------|
| `NotificationLogRepositoryInterface` | `DoctrineNotificationLogRepository` |
| `NotificationTemplateRepositoryInterface` | `DoctrineNotificationTemplateRepository` |
| `NotificationPreferenceRepositoryInterface` | `DoctrineNotificationPreferenceRepository` |
| `ContactResolverInterface` | `ChainContactResolver` + `!tagged_iterator app.notification_contact_resolver` (4 resolvers) |
| `SecurityUserResolverInterface` | `SecurityUserResolver` |

---

## 11. Known Gaps (priorizados)

| # | Gap | Evidencia | Prioridad |
|---|-----|-----------|-----------|
| G‑01 | **39/107 eventos sin destinatarios** (36%) — `recipients: []` en `CrossBcDomainEventHandler`; incluye TODO explícito en `PartRepaired` | verificado en código | 🔴 Alta |
| G‑02 | **54/107 eventos sin plantilla seedeada** → el dispatcher hace `continue` y NO envía nada (regla NO‑07); 9 eventos más tienen plantilla solo IN_APP | catálogo `SeedNotificationTemplatesCommand` (94 entradas, 54 eventos; 53 de ellos manejados) | 🔴 Alta |
| G‑03 | **Retry manual no reenvía**: `RetryNotificationHandler` resetea a PENDING pero no vuelve a despachar `SendNotificationMessage` → la notificación queda PENDING eternamente | `RetryNotificationHandler.php:37-39` | 🔴 Alta |
| G‑04 | **Sin migraciones** para `notification_logs`, `notification_templates`, `notification_preferences` (30 migraciones, ninguna las crea) | grep `migrations/` | 🔴 Alta |
| G‑05 | **Tests insuficientes**: 8 archivos / 81 tests; sin tests de Domain entities, resolvers, dispatcher, channels, 5/8 commands, 5 queries, cleanup, seed | `tests/Unit/Notification/` | 🟠 Media |
| G‑06 | **SMS stub** (log-only) — sin provider real | `SmsChannelSender` | 🟡 Media |
| G‑07 | **Inbox por employeeId**: las notificaciones `RecipientType::USER` (Identity: seguridad, roles) no aparecen en el inbox web del usuario (filtra por employeeId) | `ListInboxNotifications` / `MarkAllNotificationsRead` | 🟡 Media |
| G‑08 | **WhatsApp stub** (decisión MVP vigente — Twilio/WATI producción) | `WhatsAppChannelSender` | 🟡 Baja |
| G‑09 | **TODO obsoleto** en `onStockBelowThreshold` (dice que falta LocationContactResolver, ya existe) | `CrossBcDomainEventHandler.php` | ⚪ Limpieza |

---

## 12. Tests (8 archivos / 81 tests)

| Archivo | Tests |
|---------|-------|
| `CrossBcDomainEventHandlerTest` | 29 (cubre 27% de los 107 eventos + caso unhandled) |
| `TemplateVariableResolverTest` | 9 |
| `SendNotificationMessageHandlerTest` | 9 |
| `ApiNotificationInboxControllerTest` | 9 |
| `ApiNotificationPreferenceControllerTest` | 8 |
| `ApiNotificationTemplateControllerTest` | 7 |
| `CreateNotificationTemplateHandlerTest` | 5 |
| `ApiNotificationLogControllerTest` | 5 |

Sin tests: entities del Domain, `NotificationDispatcher`, contact resolvers, channels, `SeedNotificationTemplatesCommand`, `CleanupNotificationsCommand`, handlers restantes.

---

## 13. Resumen de estado por componente

| Componente | Estado | Detalle |
|------------|--------|---------|
| Domain (entities, enums, VOs, interfaces, exceptions) | ✅ 100% | completo |
| Application (8 commands, 5 queries, handlers, responses) | ✅ 100% | completo |
| Persistence (3 repos Doctrine, 3 mappings, 7 DBAL types) | ✅ 100% | completo (pero sin migraciones — G‑04) |
| Channels | ⚠️ 50% | Email real + In-App; SMS y WhatsApp stubs |
| Presentation (8 controllers + Twig + Stimulus) | ✅ 100% | completo |
| Cross-BC wiring (handler + dispatcher + resolvers) | ⚠️ ~55% | 107 mapeados; 68 con destinatario; 53 con plantilla |
| Retención 3 meses | ✅ 100% | `orbis:notifications:cleanup` (requiere cron) |
| Tests | ⚠️ ~35% | 81 tests, sin Domain coverage |
| Migraciones | ❌ 0% | ninguna tabla versionada |

**Evaluación global: ~75%** (estructura 100%, cadena cross-BC funcional ~55%, tests/migraciones pendientes).
