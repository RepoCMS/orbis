<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\RoleNameException;

final readonly class RoleNameValueObject
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 50;

    private string $name;

    public function __construct(string $name)
    {
        $normalized = trim($name);

        if ($normalized === '') {
            throw new RoleNameException('El nombre del rol no puede estar vacío.');
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw new RoleNameException(
                sprintf('El nombre del rol debe tener al menos %d caracteres.', self::MIN_LENGTH)
            );
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new RoleNameException(
                sprintf('El nombre del rol no puede exceder los %d caracteres.', self::MAX_LENGTH)
            );
        }

        $this->name = $normalized;
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
