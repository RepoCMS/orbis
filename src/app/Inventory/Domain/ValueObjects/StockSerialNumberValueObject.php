<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\SerializedStockIdException;

/**
 * Número de serie de una unidad física de inventario (repuesto, componente, etc.).
 * Alfanumérico con guiones. Máximo 100 caracteres.
 */
final readonly class StockSerialNumberValueObject
{
    private const MAX_LENGTH = 100;

    private string $serial;

    public function __construct(string $serial)
    {
        $normalized = strtoupper(trim($serial));

        if ($normalized === '') {
            throw new SerializedStockIdException('El número de serie no puede estar vacío.');
        }

        if (strlen($normalized) > self::MAX_LENGTH) {
            throw new SerializedStockIdException(
                sprintf('El número de serie no puede superar %d caracteres.', self::MAX_LENGTH)
            );
        }

        $this->serial = $normalized;
    }

    public static function fromString(string $serial): self
    {
        return new self($serial);
    }

    public function __toString(): string
    {
        return $this->serial;
    }
}
