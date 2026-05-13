<?php

declare(strict_types=1);

namespace App\Identity\Domain\Service;

use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Identity\Domain\ValueObjects\RawPasswordValueObject;

/**
 * Puerto de dominio para hashear contraseñas.
 * La implementación concreta (bcrypt, argon2, etc.) vive en Infrastructure.
 */
interface PasswordHasherInterface
{
    public function hash(RawPasswordValueObject $rawPassword): HashedPasswordValueObject;
}
