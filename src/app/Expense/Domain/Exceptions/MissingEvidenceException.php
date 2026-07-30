<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class MissingEvidenceException extends DomainException
{
    public static function forItem(string $itemId, string $itemType): self
    {
        return new self(
            sprintf(
                'El item %s de tipo "%s" requiere un comprobante obligatorio antes de enviar la liquidación',
                $itemId,
                $itemType
            )
        );
    }

    public static function forMultipleItems(int $count): self
    {
        return new self(
            sprintf(
                '%d item(s) requieren comprobante obligatorio antes de enviar la liquidación',
                $count
            )
        );
    }
}
