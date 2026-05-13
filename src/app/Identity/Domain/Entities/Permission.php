<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entities;

use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;

/**
 * Permiso del sistema.
 * Entidad de catálogo con ciclo de vida independiente.
 * Clave con formato "módulo.acción" (e.g., "user.create", "employee.delete").
 */
final class Permission
{
    private function __construct(
        private PermissionIdValueObject $id,
        private PermissionKeyValueObject $key,
        private string $description,
    ) {
    }

    public static function define(
        PermissionIdValueObject $id,
        PermissionKeyValueObject $key,
        string $description,
    ): self {
        return new self($id, $key, $description);
    }

    public function id(): PermissionIdValueObject
    {
        return $this->id;
    }

    public function key(): PermissionKeyValueObject
    {
        return $this->key;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function changeDescription(string $description): void
    {
        $this->description = trim($description);
    }
}
