<?php

declare(strict_types=1);

namespace App\Identity\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

class PermissionNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('No se encontró el permiso con ID "%s".', $id));
    }
}
