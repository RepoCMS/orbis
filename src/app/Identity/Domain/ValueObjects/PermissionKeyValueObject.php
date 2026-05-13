<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\PermissionKeyException;

/**
 * Clave única de un permiso con formato "módulo.acción".
 * Ejemplo: "user.create", "employee.delete", "client.view".
 */
final readonly class PermissionKeyValueObject
{
    private const PATTERN = '/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/';
    private const MAX_LENGTH = 100;

    private string $key;

    public function __construct(string $key)
    {
        $normalized = mb_strtolower(trim($key));

        if ($normalized === '') {
            throw new PermissionKeyException('La clave del permiso no puede estar vacía.');
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new PermissionKeyException(
                sprintf('La clave del permiso no puede exceder los %d caracteres.', self::MAX_LENGTH)
            );
        }

        if (!preg_match(self::PATTERN, $normalized)) {
            throw new PermissionKeyException(
                'La clave del permiso debe tener el formato "módulo.acción" (solo letras minúsculas, números y guiones bajos).'
            );
        }

        $this->key = $normalized;
    }

    public static function fromString(string $key): self
    {
        return new self($key);
    }

    public function __toString(): string
    {
        return $this->key;
    }
}
