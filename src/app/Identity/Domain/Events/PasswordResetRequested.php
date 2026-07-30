<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un usuario solicita el restablecimiento de contraseña.
 *
 * Listeners típicos:
 *   - Enviar email con el enlace/token de restablecimiento.
 *   - Registrar auditoría de solicitud de cambio.
 */
final class PasswordResetRequested extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
    ) {
        parent::__construct();
    }
}
