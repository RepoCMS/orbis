<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un nuevo rol es creado en el sistema.
 *
 * Listeners típicos:
 *   - Registrar en el log de auditoría.
 *   - Invalidar caché de roles/permisos.
 */
final class RoleCreated extends DomainEvent
{
    public function __construct(
        public readonly string $roleId,
        public readonly string $roleName,
    ) {
        parent::__construct();
    }
}
