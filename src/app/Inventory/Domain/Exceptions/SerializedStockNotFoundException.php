<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class SerializedStockNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Stock serializado con ID "%s" no encontrado.', $id));
    }

    public static function withSerial(string $serial): self
    {
        return new self(sprintf('No existe stock con número de serie "%s".', $serial));
    }
}
