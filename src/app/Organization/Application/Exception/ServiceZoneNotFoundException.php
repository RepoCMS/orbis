<?php

declare(strict_types=1);

namespace App\Organization\Application\Exception;

use App\Shared\Exceptions\DomainException;

final class ServiceZoneNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Zona de servicio con ID "%s" no encontrada.', $id));
    }
}
