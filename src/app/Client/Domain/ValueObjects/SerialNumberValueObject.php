<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\SerialNumberException;

/**
 * Número de serie de un terminal.
 * Exactamente 10 dígitos alfanuméricos.
 */
final readonly class SerialNumberValueObject
{
    private const LENGTH   = 10;
    private const PATTERN  = '/^[A-Za-z0-9]{10}$/';

    private string $serialNumber;

    public function __construct(string $serialNumber)
    {
        $normalized = trim($serialNumber);

        if ($normalized === '') {
            throw new SerialNumberException('El número de serie no puede estar vacío.');
        }

        if (!preg_match(self::PATTERN, $normalized)) {
            throw new SerialNumberException(
                sprintf(
                    'El número de serie debe tener exactamente %d dígitos alfanuméricos.',
                    self::LENGTH
                )
            );
        }

        $this->serialNumber = $normalized;
    }

    public static function fromString(string $serialNumber): self
    {
        return new self($serialNumber);
    }

    public function __toString(): string
    {
        return $this->serialNumber;
    }
}
