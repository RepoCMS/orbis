<?php

declare(strict_types=1);

namespace App\Client\Application\Exception;

use App\Shared\Exceptions\DomainException;

final class SiteNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Sucursal con ID "%s" no encontrada.', $id));
    }
}
