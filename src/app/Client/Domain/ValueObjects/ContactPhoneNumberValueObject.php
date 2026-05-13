<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ContactPhoneNumberException;

/**
 * Teléfono ecuatoriano de contacto.
 * Exactamente 10 dígitos numéricos, debe iniciar con "09".
 */
final readonly class ContactPhoneNumberValueObject
{
    private const LENGTH   = 10;
    private const PATTERN  = '/^09\d{8}$/';

    private string $phoneNumber;

    public function __construct(string $phoneNumber)
    {
        $normalized = trim($phoneNumber);

        if ($normalized === '') {
            throw new ContactPhoneNumberException('El número de teléfono del contacto no puede estar vacío.');
        }

        if (!preg_match(self::PATTERN, $normalized)) {
            throw new ContactPhoneNumberException(
                sprintf(
                    'El número de teléfono debe iniciar con "09" y tener exactamente %d dígitos.',
                    self::LENGTH
                )
            );
        }

        $this->phoneNumber = $normalized;
    }

    public static function fromString(string $phoneNumber): self
    {
        return new self($phoneNumber);
    }

    public function __toString(): string
    {
        return $this->phoneNumber;
    }
}
