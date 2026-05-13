<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Service;

use App\Identity\Domain\Service\PasswordHasherInterface;
use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RawPasswordValueObject;

/**
 * Implementación del hasher de contraseñas usando bcrypt (PHP nativo).
 * Coste 12 para equilibrar seguridad y rendimiento.
 */
final class NativePasswordHasher implements PasswordHasherInterface
{
    private const BCRYPT_COST = 12;

    public function hash(RawPasswordValueObject $rawPassword): HashedPasswordValueObject
    {
        $hashed = password_hash((string) $rawPassword, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);

        return new HashedPasswordValueObject($hashed);
    }

    public function verify(string $rawPassword, HashedPasswordValueObject $hashed): bool
    {
        return password_verify($rawPassword, (string) $hashed);
    }
}
