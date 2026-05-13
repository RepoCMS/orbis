<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\TokenException;

/**
 * Hash SHA-256 del PlainToken. Es lo único que se persiste en BD.
 * Nunca se expone fuera del BC Identity.
 */
final readonly class TokenHashValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $value)) {
            throw new TokenException('El hash del token debe ser una cadena hexadecimal de 64 caracteres.');
        }

        $this->value = $value;
    }

    public static function fromPlainToken(PlainTokenValueObject $token): self
    {
        return new self(hash('sha256', (string) $token));
    }

    public function matches(PlainTokenValueObject $plainToken): bool
    {
        return hash_equals($this->value, hash('sha256', (string) $plainToken));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
