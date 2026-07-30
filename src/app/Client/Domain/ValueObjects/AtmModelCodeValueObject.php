<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\AtmModelCodeException;

/**
 * Código del modelo de ATM.
 *
 * Valor opcional que representa el modelo del ATM (ej: mx8200st, mx5800t).
 * Se valida que no esté vacío y no exceda 30 caracteres.
 * La validación contra el enum AtmModel de Support BC se realiza
 * en la capa de Application (Controller/Handler).
 */
final readonly class AtmModelCodeValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new AtmModelCodeException('El código de modelo ATM no puede estar vacío.');
        }

        if (mb_strlen($normalized) > 30) {
            throw new AtmModelCodeException('El código de modelo ATM no debe exceder 30 caracteres.');
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(object $other): bool
    {
        return $other instanceof self && $other->value === $this->value;
    }
}
