<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un usuario completa la configuración de MFA.
 *
 * Listeners típicos:
 *   - Registrar en el log de auditoría.
 *   - Notificar al usuario del cambio de seguridad.
 */
final class MfaSetupCompleted extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
    ) {
        parent::__construct();
    }
}
