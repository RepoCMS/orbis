<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando el usuario administrador inicial es creado (seeding).
 *
 * Listeners típicos:
 *   - Enviar credenciales iniciales al administrador.
 *   - Registrar en el log de auditoría del sistema.
 */
final class AdminSeeded extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
    ) {
        parent::__construct();
    }
}
