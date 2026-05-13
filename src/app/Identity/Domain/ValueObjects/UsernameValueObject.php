<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\UserUsernameException;

final readonly class UsernameValueObject
{
    private const MIN_LENGTH = 5;
    private const MAX_LENGTH = 15;
    private const PATTERN = '/^[a-z0-9._-]+$/';

    private string $username;

    public function __construct(string $username)
    {
        $normalized = mb_strtolower(trim($username));

        if ($normalized === '') {
            throw new UserUsernameException('El nombre de usuario no puede estar vacío.');
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw new UserUsernameException(
                sprintf('El nombre de usuario debe tener al menos %d caracteres.', self::MIN_LENGTH)
            );
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new UserUsernameException(
                sprintf('El nombre de usuario no puede exceder los %d caracteres.', self::MAX_LENGTH)
            );
        }

        if (!preg_match(self::PATTERN, $normalized)) {
            throw new UserUsernameException(
                'El nombre de usuario solo puede contener letras minúsculas, números, puntos, guiones y guiones bajos.'
            );
        }

        $this->username = $normalized;
    }

    public static function fromString(string $username): self
    {
        return new self($username);
    }

    public function __toString(): string
    {
        return $this->username;
    }
}
