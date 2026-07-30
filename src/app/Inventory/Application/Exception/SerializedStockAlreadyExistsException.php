<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class SerializedStockAlreadyExistsException extends ApplicationException
{
    public static function withSerial(string $serial): self
    {
        return new self(sprintf('El número de serie "%s" ya existe para este producto.', $serial));
    }
}
