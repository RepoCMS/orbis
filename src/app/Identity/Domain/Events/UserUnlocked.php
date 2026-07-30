<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando una cuenta de usuario es desbloqueada.
 *
 * Listeners típicos:
 *   - Notificar al usuario del desbloqueo.
 *   - Registrar en el log de auditoría.
 */
final class UserUnlocked extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
    ) {
        parent::__construct();
    }
}
