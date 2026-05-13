<?php

declare(strict_types=1);

namespace App\Identity\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

class RoleNameAlreadyExistsException extends ApplicationException
{
    public static function withName(string $name): self
    {
        return new self(sprintf('El nombre de rol "%s" ya está en uso.', $name));
    }
}
