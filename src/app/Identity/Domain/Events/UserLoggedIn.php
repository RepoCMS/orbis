<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un usuario inicia sesión exitosamente.
 *
 * Listeners típicos:
 *   - Registrar en el historial de autenticación.
 *   - Actualizar último acceso del usuario.
 */
final class UserLoggedIn extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $ipAddress,
        public readonly string $timestamp,
    ) {
        parent::__construct();
    }
}
