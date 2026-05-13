<?php

declare(strict_types=1);

namespace App\Identity\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

class UsernameAlreadyExistsException extends ApplicationException
{
    public static function withUsername(string $username): self
    {
        return new self(sprintf('El nombre de usuario "%s" ya está en uso.', $username));
    }
}
