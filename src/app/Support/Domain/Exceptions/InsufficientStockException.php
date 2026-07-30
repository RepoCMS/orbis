<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InsufficientStockException extends DomainException
{
    public static function forProduct(string $productCode, int $requested, int $available): self
    {
        return new self(
            sprintf(
                'Stock insuficiente para el producto "%s": solicitado %d, disponible %d.',
                $productCode,
                $requested,
                $available,
            )
        );
    }
}
