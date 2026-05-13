<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\ProductNotFoundException;

final readonly class ProductNameValueObject
{
    private string $name;

    public function __construct(string $name)
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new ProductNotFoundException('El nombre del producto no puede estar vacío.');
        }

        if (strlen($trimmed) > 150) {
            throw new ProductNotFoundException('El nombre del producto no puede superar 150 caracteres.');
        }

        $this->name = $trimmed;
    }

    public function value(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
