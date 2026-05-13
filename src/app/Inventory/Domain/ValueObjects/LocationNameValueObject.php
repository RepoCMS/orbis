<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\LocationNameException;

final readonly class LocationNameValueObject
{
    private string $name;

    public function __construct(string $name)
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new LocationNameException('El nombre de la ubicación no puede estar vacío.');
        }

        if (strlen($trimmed) > 100) {
            throw new LocationNameException('El nombre de la ubicación no puede superar 100 caracteres.');
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
