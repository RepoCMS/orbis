<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class UserOrphanException extends DomainException
{
    public static function withUserId(string $userId): self
    {
        return new self(sprintf(
            'El usuario %s no está vinculado a ningún empleado o contacto.',
            $userId,
        ));
    }
}
