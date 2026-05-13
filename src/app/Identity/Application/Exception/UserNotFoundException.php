<?php

declare(strict_types=1);

namespace App\Identity\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

class UserNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('No se encontró el usuario con ID "%s".', $id));
    }
}
