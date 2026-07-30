<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class EntryItemNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Ítem de entrada con ID "%s" no encontrado.', $id));
    }
}
