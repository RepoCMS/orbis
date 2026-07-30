<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un usuario cambia su contraseña (no restablecimiento).
 *
 * Listeners típicos:
 *   - Notificar al usuario del cambio de contraseña.
 *   - Invalidar todas las sesiones activas.
 *   - Registrar en el log de auditoría.
 */
final class PasswordChanged extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
    ) {
        parent::__construct();
    }
}
