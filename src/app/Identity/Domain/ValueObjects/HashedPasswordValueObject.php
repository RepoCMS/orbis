<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\UserPasswordException;

/**
 * Representa una contraseña ya hasheada.
 * La entidad User almacena este valor; el hasheo ocurre en infraestructura.
 */
final readonly class HashedPasswordValueObject
{
    private string $hash;

    public function __construct(string $hash)
    {
        if (trim($hash) === '') {
            throw new UserPasswordException('El hash de la contraseña no puede estar vacío.');
        }

        $this->hash = $hash;
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}
