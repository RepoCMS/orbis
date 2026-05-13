<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\DomainException;

final class StockNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Stock con id "%s" no encontrado.', $id));
    }
}
