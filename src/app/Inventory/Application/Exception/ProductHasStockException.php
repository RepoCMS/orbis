<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class ProductHasStockException extends ApplicationException
{
    public static function cannotChangeTrackingType(): self
    {
        return new self(
            'El producto tiene stock registrado. Vacíe todo el stock antes de cambiar el tipo de rastreo.'
        );
    }
}
