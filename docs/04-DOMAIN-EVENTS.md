# Domain Events — Orbis

> Última actualización: 2026-05-05

---

## ¿Por qué Domain Events?

Un **Domain Event** representa algo que **ya ocurrió** en el dominio y que puede ser relevante para otros Bounded Contexts o para efectos secundarios (emails, auditoría, notificaciones, inventario).

Sin eventos, los Bounded Contexts se acoplan directamente:

```
// ❌ MAL — EmployeeController (Organization BC) importa Identity BC
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
```

Con eventos, cada BC es autónomo y se comunica a través de eventos:

```
EmployeeController → ProvisionEmployeeAccessHandler (Organization BC)
                              ↓
                     publica EmployeeAccessProvisioned
                              ↓
                     (futuro) Listener envía email de bienvenida
```

---

## Infraestructura (Shared Kernel)

### `DomainEvent` — base abstracta

```
src/app/Shared/Domain/Event/DomainEvent.php
```

Clase base pura PHP (sin dependencias). Todos los eventos la extienden.
Provee `occurredAt(): DateTimeImmutable` automáticamente.

```php
abstract class DomainEvent
{
    public function occurredAt(): DateTimeImmutable { ... }
}
```

### `DomainEventPublisherInterface` — puerto

```
src/app/Shared/Domain/Event/DomainEventPublisherInterface.php
```

Interfaz que inyectan los Application Handlers para publicar eventos.

```php
interface DomainEventPublisherInterface
{
    public function publish(DomainEvent ...$events): void;
}
```

### `SymfonyDomainEventPublisher` — adaptador

```
src/app/Shared/Infrastructure/Event/SymfonyDomainEventPublisher.php
```

Implementación que usa el `EventDispatcherInterface` de Symfony (compatible PSR-14).
Los eventos se despachan como objetos PHP directamente — sin wrappers.

---

## Cómo publicar un evento

Desde cualquier Application Handler, inyectar `DomainEventPublisherInterface` y llamarlo después de persistir:

```php
final readonly class CreateTicketHandler
{
    public function __construct(
        private TicketRepositoryInterface     $tickets,
        private DomainEventPublisherInterface $publisher,
    ) {}

    public function __invoke(CreateTicketCommand $command): string
    {
        $ticket = Ticket::open(...);
        $this->tickets->save($ticket);

        $this->publisher->publish(new TicketCreated(
            ticketId:    (string) $ticket->id(),
            clientId:    $command->clientId,
            technicianId: $command->technicianId,
        ));

        return (string) $ticket->id();
    }
}
```

> **Regla**: publicar el evento DESPUÉS de persistir el agregado, nunca antes.

---

## Cómo escuchar un evento (Listener)

Crear un listener en la capa `Infrastructure/EventListener/` del BC que reacciona.
Usar el atributo `#[AsEventListener]` de Symfony — se registra automáticamente gracias a `autoconfigure: true`.

```php
// src/app/Support/Infrastructure/EventListener/NotifyTechnicianOnTicketCreated.php

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: TicketCreated::class)]
final readonly class NotifyTechnicianOnTicketCreated
{
    public function __construct(
        private NotificationServiceInterface $notifier,
        private TechnicianRepositoryInterface $technicians,
    ) {}

    public function __invoke(TicketCreated $event): void
    {
        $tech = $this->technicians->findById($event->technicianId);
        $this->notifier->sendWhatsApp($tech->phone(), "Nuevo ticket asignado: {$event->ticketId}");
    }
}
```

---

## Catálogo de eventos del sistema

### Identity BC

| Evento | Publicado en | Datos | Listeners planificados |
|---|---|---|---|
| `UserRegistered` | `RegisterUserHandler` | userId, username, roleId | Auditoría de altas |

### Organization BC

| Evento | Publicado en | Datos | Listeners planificados |
|---|---|---|---|
| `EmployeeCreated` | `CreateEmployeeHandler` | employeeId, firstName, lastName, email | Notificación RRHH |
| `EmployeeAccessProvisioned` | `ProvisionEmployeeAccessHandler` | employeeId, userId | Email bienvenida sistema |

### Client BC

| Evento | Publicado en | Datos | Listeners planificados |
|---|---|---|---|
| `ContactAccessProvisioned` | `ProvisionContactAccessHandler` | contactId, clientId, userId | Email bienvenida portal |

### Support BC (planificados)

| Evento | Publicado en | Datos | Listeners planificados |
|---|---|---|---|
| `TicketCreated` | `CreateTicketHandler` | ticketId, clientId, terminalId | Email cliente, WhatsApp técnico |
| `TicketAssigned` | `AssignTicketHandler` | ticketId, technicianId | WhatsApp técnico, Email supervisor |
| `TicketResolved` | `ResolveTicketHandler` | ticketId, resolution | Email cliente con informe |
| `TicketEscalated` | `EscalateTicketHandler` | ticketId, reason | Email supervisor |
| `TechnicianSetStandby` | `SetTechnicianStandbyHandler` | technicianId, scheduleId | Notificación interna |

### Inventory BC (planificados)

| Evento | Publicado en | Datos | Listeners planificados |
|---|---|---|---|
| `StockBelowThreshold` | `ConsumeStockHandler` | partId, warehouseId, currentQty | Email alerta compras |
| `SparePartRepaired` | `CompleteRepairHandler` | partId, repairId | Mover a stock disponible |
| `PurchaseOrderReceived` | `ReceivePurchaseOrderHandler` | orderId, items[] | Incrementar stock |

---

## Flujos cross-BC implementados

### Crear acceso para Empleado

```
POST /employees/{id}/create-user
        ↓
EmployeeController::createUser()
        ↓
ProvisionEmployeeAccessHandler (Organization BC)
    ├── RegisterUserHandler (Identity BC)         → publica UserRegistered
    ├── LinkUserToEmployeeHandler (Organization)
    ├── GenerateActivationTokenHandler (Identity) → envía email activación
    └── publica EmployeeAccessProvisioned
```

### Crear acceso para Contacto de Cliente

```
POST /clients/{clientId}/contacts/{contactId}/create-user
        ↓
ContactController::createContactUser()
        ↓
ProvisionContactAccessHandler (Client BC)
    ├── RegisterUserHandler (Identity BC)         → publica UserRegistered
    ├── LinkUserToContactHandler (Client)
    ├── GenerateActivationTokenHandler (Identity) → envía email activación
    └── publica ContactAccessProvisioned
```

---

## Convenciones de naming

| Regla | Ejemplo |
|---|---|
| Pasado del verbo, PascalCase | `TicketCreated`, `StockBelowThreshold` |
| Inmutables, solo `readonly` properties | `public readonly string $ticketId` |
| Extender `DomainEvent` obligatorio | `final class TicketCreated extends DomainEvent` |
| Ubicación: `{BC}/Domain/Events/` | `Support/Domain/Events/TicketCreated.php` |
| Listeners en `{BC}/Infrastructure/EventListener/` | `Support/Infrastructure/EventListener/NotifyTechnicianOnTicketCreated.php` |
| Listener naming: Verbo + "On" + Evento | `NotifyTechnicianOnTicketCreated` |

---

## Ejecución: síncrona vs asíncrona

**Actualmente: síncrona** (mismo request HTTP, misma transacción).
Ideal para listeners rápidos (email, log, auditoría simple).

**Futuro con Symfony Messenger**: para operaciones lentas o que puedan fallar sin bloquear al usuario (WhatsApp, reportes, integraciones externas), los listeners se mueven a mensajes en cola:

```php
// Listener que despacha a queue en vez de ejecutar directamente
#[AsEventListener(event: TicketCreated::class)]
final class DispatchTicketCreatedToQueue
{
    public function __invoke(TicketCreated $event): void
    {
        $this->bus->dispatch(new SendWhatsAppToTechnicianMessage($event->technicianId));
    }
}
```

---

## Archivos creados

```
src/app/Shared/Domain/Event/
    DomainEvent.php                          ← base abstracta
    DomainEventPublisherInterface.php        ← puerto

src/app/Shared/Infrastructure/Event/
    SymfonyDomainEventPublisher.php          ← adaptador Symfony

src/app/Identity/Domain/Events/
    UserRegistered.php

src/app/Organization/Domain/Events/
    EmployeeCreated.php
    EmployeeAccessProvisioned.php

src/app/Organization/Application/Command/ProvisionEmployeeAccess/
    ProvisionEmployeeAccessCommand.php
    ProvisionEmployeeAccessHandler.php       ← orquesta cross-BC

src/app/Client/Domain/Events/
    ContactAccessProvisioned.php

src/app/Client/Application/Command/ProvisionContactAccess/
    ProvisionContactAccessCommand.php
    ProvisionContactAccessHandler.php        ← orquesta cross-BC
```
