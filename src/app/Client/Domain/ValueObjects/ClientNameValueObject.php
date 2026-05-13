<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ClientNameException;

final readonly class ClientNameValueObject
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 100;

    private string $name;

    public function __construct(string $name)
    {
        $normalized = (string) preg_replace('/\s+/u', ' ', trim($name));

        if ($normalized === '') {
            throw new ClientNameException('El nombre del cliente no puede estar vacío.');
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw new ClientNameException(
                sprintf('El nombre del cliente debe tener al menos %d caracteres.', self::MIN_LENGTH)
            );
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new ClientNameException(
                sprintf('El nombre del cliente no puede exceder los %d caracteres.', self::MAX_LENGTH)
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
