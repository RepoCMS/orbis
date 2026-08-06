<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use App\Shared\Exceptions\DomainException;

abstract readonly class AbstractUlid
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    private string $id;

    public function __construct(string $id)
    {
        $normalized = strtoupper(trim($id));

        if (!preg_match(self::ULID_PATTERN, $normalized)) {
            throw static::invalidIdException();
        }

        $this->id = $normalized;
    }

    public static function fromString(string $id): static
    {
        return new static($id);
    }

    public function __toString(): string
    {
        return $this->id;
    }

    abstract protected static function invalidIdException(): DomainException;
}
