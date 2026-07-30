<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un administrador deshabilita el MFA de un usuario.
 *
 * Listeners típicos:
 *   - Notificar al usuario de la desactivación del MFA.
 *   - Registrar en el log de auditoría con el ID del admin.
 */
final class MfaDisabledByAdmin extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $disabledBy,
    ) {
        parent::__construct();
    }
}
