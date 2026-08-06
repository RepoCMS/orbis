<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\EmployeeIdentificationNumberException;

readonly class EmployeeIdentificationNumberValueObject
{
    private string $identificationNumber;

    public function __construct(string $identificationNumber)
    {
        $normalizedIdentificationNumber = trim($identificationNumber);

        if ($normalizedIdentificationNumber === '') {
            throw new EmployeeIdentificationNumberException('El número de identificación no puede estar vacío.');
        }

        if (!preg_match('/^[0-9]+$/', $normalizedIdentificationNumber)) {
            throw new EmployeeIdentificationNumberException('El número de identificación debe contener solo dígitos del 0 al 9.');
        }

        if (strlen($normalizedIdentificationNumber) > 10) {
            throw new EmployeeIdentificationNumberException('El número de identificación no puede exceder los 10 caracteres.');
        }

        $this->identificationNumber = $normalizedIdentificationNumber;
    }

    public static function fromString(string $identificationNumber): self
    {
        return new self($identificationNumber);
    }

    public function __toString(): string
    {
        return $this->identificationNumber;
    }
}
