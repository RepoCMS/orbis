<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\TokenException;

/**
 * Token en texto plano: 32 bytes criptográficamente seguros representados como hex (64 chars).
 * Solo existe en memoria — nunca se persiste.
 */
final readonly class PlainTokenValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $value)) {
            throw new TokenException('El token debe ser una cadena hexadecimal de 64 caracteres.');
        }

        $this->value = $value;
    }

    /**
     * Genera un token seguro usando random_bytes.
     */
    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(32)));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
