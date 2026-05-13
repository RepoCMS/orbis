<?php

declare(strict_types=1);

namespace App\Identity\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

class PermissionKeyAlreadyExistsException extends ApplicationException
{
    public static function withKey(string $key): self
    {
        return new self(sprintf('La clave de permiso "%s" ya está en uso.', $key));
    }
}
