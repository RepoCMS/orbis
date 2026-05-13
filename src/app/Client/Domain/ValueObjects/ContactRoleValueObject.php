<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ContactRoleException;

/**
 * Rol o cargo del contacto en su organización.
 * Ej: "Supervisor de ATMs", "Gerente de Operaciones".
 * Texto libre normalizado, 2–100 caracteres.
 */
final readonly class ContactRoleValueObject
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 100;

    private string $role;

    public function __construct(string $role)
    {
        $normalized = (string) preg_replace('/\s+/u', ' ', trim($role));

        if ($normalized === '') {
            throw new ContactRoleException('El rol del contacto no puede estar vacío.');
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw new ContactRoleException(
                sprintf('El rol del contacto debe tener al menos %d caracteres.', self::MIN_LENGTH)
            );
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new ContactRoleException(
                sprintf('El rol del contacto no puede exceder los %d caracteres.', self::MAX_LENGTH)
            );
        }

        $this->role = $normalized;
    }

    public static function fromString(string $role): self
    {
        return new self($role);
    }

    public function __toString(): string
    {
        return $this->role;
    }
}
