<?php

declare(strict_types=1);

namespace App\Identity\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class EmailNotFoundException extends ApplicationException
{
    public static function withEmail(string $email): self
    {
        return new self(sprintf('No se encontró ningún usuario con el email "%s".', $email));
    }
}
