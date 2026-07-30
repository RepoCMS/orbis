<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un rol es eliminado del sistema.
 *
 * Listeners típicos:
 *   - Reasignar usuarios a un rol por defecto.
 *   - Invalidar caché de roles/permisos.
 *   - Registrar en el log de auditoría.
 */
final class RoleDeleted extends DomainEvent
{
    public function __construct(
        public readonly string $roleId,
    ) {
        parent::__construct();
    }
}
