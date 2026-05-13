<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class PurchaseOrderReferenceAlreadyExistsException extends ApplicationException
{
    public static function withReference(string $reference): self
    {
        return new self(sprintf('Ya existe una orden de compra con la referencia "%s".', $reference));
    }
}
