<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un nuevo User es registrado en el sistema.
 *
 * Listeners típicos:
 *   - Enviar email de activación de cuenta.
 *   - Registrar auditoría de alta de usuario.
 */
final class UserRegistered extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $username,
        public readonly string $roleId,
    ) {
        parent::__construct();
    }
}
