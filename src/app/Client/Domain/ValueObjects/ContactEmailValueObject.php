<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ContactEmailException;
use App\Shared\Domain\StringNormalizer;

final readonly class ContactEmailValueObject
{
    private string $email;

    public function __construct(string $email)
    {
        $normalized = StringNormalizer::normalizeEmail($email);

        if ($normalized === '') {
            throw new ContactEmailException('El correo electrónico no puede estar vacío.');
        }

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new ContactEmailException('Formato de correo electrónico inválido.');
        }

        $this->email = $normalized;
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function __toString(): string
    {
        return $this->email;
    }
}
