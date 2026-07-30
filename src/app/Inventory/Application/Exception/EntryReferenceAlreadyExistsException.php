<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class EntryReferenceAlreadyExistsException extends ApplicationException
{
    public static function withReference(string $reference): self
    {
        return new self(sprintf('Ya existe una entrada con la referencia "%s".', $reference));
    }
}
