<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando los permisos de un rol son actualizados.
 *
 * Listeners típicos:
 *   - Invalidar caché de permisos del rol.
 *   - Registrar en el log de auditoría.
 */
final class RolePermissionsUpdated extends DomainEvent
{
    /**
     * @param string[] $permissionIds
     */
    public function __construct(
        public readonly string $roleId,
        public readonly array $permissionIds,
    ) {
        parent::__construct();
    }
}
