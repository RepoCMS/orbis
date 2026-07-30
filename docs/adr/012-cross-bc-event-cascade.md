# ADR-012: Cross-BC Event-Driven Cascade

## Status
Accepted

## Context
Las operaciones de negocio sobre entidades de Client BC (desactivar, reactivar, eliminar un cliente) requieren una cascada de acciones en otros Bounded Contexts:

1. **Client → Contact**: al desactivar un cliente, deben desactivarse todos sus contactos; al reactivar, reactivarse; al eliminar, soft-delete de contactos.
2. **Client → Notification**: los contactos del cliente deben recibir notificaciones sobre cambios de estado.
3. **Client → Identity**: al desactivar un contacto, su usuario vinculado debe bloquearse (futuro).

Inicialmente, los handlers `DeactivateClientHandler` y `DeleteClientHandler` inyectaban directamente handlers del mismo BC (Contact BC) como `DeactivateContactHandler`, ejecutando la cascada **de forma síncrona y acoplada**. Esto:
- Violaba el principio DDD de que un BC no debe instanciar ni orquestar directamente entidades de otro BC.
- Creaba dependencias de infraestructura directas entre handlers.
- Hacía imposible la reintención o compensación asíncrona en caso de fallo.
- No permitía notificar a otros BCs (Notification, Identity) sin añadir más dependencias directas.

Además, el principio arquitectónico de Cross-BC establece: **solo IDs entre BCs, nunca instanciar entidades de otro BC**.

## Decision
Eliminar el bypass síncrono entre handlers de distintos BCs. En su lugar:

1. **Domain Events por operación**: Cada handler de Client BC (`DeactivateClientHandler`, `ActivateClientHandler`, `DeleteClientHandler`) publica un Domain Event (`ClientDeactivated`, `ClientReactivated`, `ClientDeleted`) después de persistir el cambio.

2. **CrossBcDomainEventHandler en Notification BC**: Un único handler (`src/app/Notification/Infrastructure/Messenger/Handler/CrossBcDomainEventHandler.php`) suscribe todos los eventos cross-BC via Symfony Messenger (async). Este handler:
   - Recibe el evento serializado como `DomainEventMessage`
   - Reconstruye el evento original con `restoreEvent()`
   - Ejecuta la cascada correspondiente (de/reactivar contactos, notificar)
   - Los errores se loguean pero no bloquean el flujo (fire-and-forget pattern)

3. **Comunicación async via Messenger**: Los eventos cruzan el bus de Messenger con transporte async, lo que permite:
   - Reintentos automáticos en caso de fallo transitorio
   - Desacoplamiento temporal (el handler de Client no espera a que la cascada termine)
   - Escalabilidad (la cascada se procesa en un worker separado)

## Consequences

### Positive
- **Pureza DDD**: Client BC no sabe de Contact, Notification ni Identity BC. Solo publica eventos sobre sí mismo.
- **Desacoplamiento**: agregar nuevas reacciones a la cascada (ej. bloquear usuario en Identity BC) solo requiere agregar un nuevo subscriber en el `CrossBcDomainEventHandler`, sin modificar los handlers de Client.
- **Resiliencia**: fallos en notificaciones no afectan la operación principal (desactivar cliente).
- **Idempotencia**: el uso de `ProcessedCommand` en Shared Kernel evita duplicados en la recepción de eventos.
- **Trazabilidad**: cada evento publicado queda registrado, permitiendo auditoría.

### Negative
- **Eventual consistency**: la cascada no es atómica. Entre la desactivación del cliente y la desactivación de sus contactos hay una ventana de inconsistencia. Para el caso de uso (cascada administrativa), es aceptable.
- **Complejidad operativa**: se requiere un worker de Messenger corriendo para procesar los eventos async.
- **Logging necesario**: los errores en la cascada se loguean pero no se reintentan automáticamente (por ahora).

### Eventos gestionados
| Evento | Handler | Acción cascada |
|--------|---------|----------------|
| `ClientDeactivated` | `CrossBcDomainEventHandler::onClientDeactivated()` | Desactivar contactos activos + notificar |
| `ClientReactivated` | `CrossBcDomainEventHandler::onClientReactivated()` | Reactivar contactos inactivos + notificar |
| `ClientDeleted` | `CrossBcDomainEventHandler::onClientDeleted()` | Soft-delete contactos + notificar |
