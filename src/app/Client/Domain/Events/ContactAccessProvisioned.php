<?php

declare(strict_types=1);

namespace App\Client\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando se crea y vincula una cuenta de acceso a un contacto de cliente.
 *
 * Listeners típicos:
 *   - Enviar email de bienvenida al portal de clientes.
 *   - Registrar auditoría de alta de acceso.
 */
final class ContactAccessProvisioned extends DomainEvent
{
    public function __construct(
        public readonly string $contactId,
        public readonly string $clientId,
        public readonly string $userId,
    ) {
        parent::__construct();
    }
}
