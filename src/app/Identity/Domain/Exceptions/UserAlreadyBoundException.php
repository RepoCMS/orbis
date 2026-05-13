<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

/**
 * Se lanza cuando se intenta vincular un User que ya está asignado
 * a otro Employee o Contact. Un User sólo puede pertenecer a una persona.
 */
final class UserAlreadyBoundException extends DomainException
{
    public static function withUserId(string $userId, string $boundTo): self
    {
        return new self(sprintf(
            'El usuario "%s" ya está vinculado a %s. Un usuario sólo puede pertenecer a una persona.',
            $userId,
            $boundTo,
        ));
    }
}
