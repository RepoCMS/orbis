<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\ProductIdException;

/**
 * Código de producto asignado por el proveedor o el sistema.
 * Alfanumérico, guiones y guiones bajos permitidos. Máximo 50 caracteres.
 */
final readonly class ProductCodeAssignedBySupplierValueObject
{
    private const MAX_LENGTH = 50;
    private const PATTERN    = '/^[A-Za-z0-9\-_]+$/';

    private string $code;

    public function __construct(string $code)
    {
        $normalized = strtoupper(trim($code));

        if ($normalized === '') {
            throw new ProductIdException('El código de producto no puede estar vacío.');
        }

        if (strlen($normalized) > self::MAX_LENGTH) {
            throw new ProductIdException(
                sprintf('El código de producto no puede superar %d caracteres.', self::MAX_LENGTH)
            );
        }

        if (!preg_match(self::PATTERN, $normalized)) {
            throw new ProductIdException('El código de producto solo permite letras, números, guiones y guiones bajos.');
        }

        $this->code = $normalized;
    }

    public static function fromString(string $code): self
    {
        return new self($code);
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
