<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\UserPasswordException;

/**
 * Representa una contraseña sin hashear.
 * Se utiliza únicamente en la capa de aplicación para validar reglas de complejidad
 * antes de pasar la contraseña al hasher de infraestructura.
 */
final readonly class RawPasswordValueObject
{
    private const MIN_LENGTH = 8;

    private string $password;

    public function __construct(string $password)
    {
        if ($password === '') {
            throw new UserPasswordException('La contraseña no puede estar vacía.');
        }

        if (mb_strlen($password) < self::MIN_LENGTH) {
            throw new UserPasswordException(
                sprintf('La contraseña debe tener al menos %d caracteres.', self::MIN_LENGTH)
            );
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new UserPasswordException('La contraseña debe contener al menos una letra mayúscula.');
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new UserPasswordException('La contraseña debe contener al menos una letra minúscula.');
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new UserPasswordException('La contraseña debe contener al menos un dígito.');
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            throw new UserPasswordException('La contraseña debe contener al menos un carácter especial.');
        }

        $this->password = $password;
    }

    public function __toString(): string
    {
        return $this->password;
    }
}
