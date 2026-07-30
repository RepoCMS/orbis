<?php

declare(strict_types=1);

namespace App\Identity\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

class RoleNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('No se encontró el rol con ID "%s".', $id));
    }

    public static function withSlug(string $slug): self
    {
        return new self(sprintf('No se encontró el rol con slug "%s".', $slug));
    }
}
