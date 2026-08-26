<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entities;

use App\Identity\Domain\Enums\RoleStatus;
use App\Identity\Domain\Exceptions\RoleStatusException;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;

/**
 * Rol del sistema.
 * Aggregate root que agrupa un conjunto de permisos.
 * Un rol puede estar ACTIVE o INACTIVE.
 */
final class Role
{
    /** @var array<string, PermissionIdValueObject> Indexado por ULID string para búsqueda O(1) */
    private array $permissionIds;

    /**
     * @param PermissionIdValueObject[] $permissionIds
     */
    private function __construct(
        private RoleIdValueObject $id,
        private RoleNameValueObject $name,
        private RoleSlugValueObject $slug,
        private RoleStatus $status = RoleStatus::ACTIVE,
        array $permissionIds = [],
    ) {
        $indexed = [];
        foreach ($permissionIds as $permissionId) {
            $indexed[(string) $permissionId] = $permissionId;
        }
        $this->permissionIds = $indexed;
    }

    /**
     * @param PermissionIdValueObject[] $permissionIds
     */
    public static function create(
        RoleIdValueObject $id,
        RoleNameValueObject $name,
        RoleSlugValueObject $slug,
        array $permissionIds = [],
    ): self {
        return new self($id, $name, $slug, RoleStatus::ACTIVE, $permissionIds);
    }

    public function id(): RoleIdValueObject
    {
        return $this->id;
    }

    public function name(): RoleNameValueObject
    {
        return $this->name;
    }

    public function slug(): RoleSlugValueObject
    {
        return $this->slug;
    }

    public function status(): RoleStatus
    {
        return $this->status;
    }

    /**
     * @return PermissionIdValueObject[]
     */
    public function permissionIds(): array
    {
        return array_values($this->permissionIds);
    }

    public function isActive(): bool
    {
        return $this->status === RoleStatus::ACTIVE;
    }

    public function rename(RoleNameValueObject $newName): void
    {
        $this->name = $newName;
    }

    public function activate(): void
    {
        if ($this->isActive()) {
            throw new RoleStatusException('El rol ya se encuentra activo.');
        }

        $this->status = RoleStatus::ACTIVE;
    }

    public function deactivate(): void
    {
        if (!$this->isActive()) {
            throw new RoleStatusException('El rol ya se encuentra inactivo.');
        }

        $this->status = RoleStatus::INACTIVE;
    }

    public function assignPermission(PermissionIdValueObject $permissionId): void
    {
        $key = (string) $permissionId;

        if (isset($this->permissionIds[$key])) {
            return; // Idempotente: asignar un permiso ya existente no es un error
        }

        $this->permissionIds[$key] = $permissionId;
    }

    public function revokePermission(PermissionIdValueObject $permissionId): void
    {
        $key = (string) $permissionId;

        if (!isset($this->permissionIds[$key])) {
            return; // Idempotente: revocar un permiso inexistente no es un error
        }

        unset($this->permissionIds[$key]);
    }

    public function hasPermission(PermissionIdValueObject $permissionId): bool
    {
        return isset($this->permissionIds[(string) $permissionId]);
    }
}
