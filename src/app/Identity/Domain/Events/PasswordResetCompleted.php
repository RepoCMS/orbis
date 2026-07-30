<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un usuario ha completado el restablecimiento de contraseña.
 *
 * Listeners típicos:
 *   - Notificar al usuario que su contraseña ha sido cambiada.
 *   - Invalidar todas las sesiones activas del usuario.
 *   - Registrar auditoría de cambio de contraseña.
 */
final class PasswordResetCompleted extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
    ) {
        parent::__construct();
    }
}
