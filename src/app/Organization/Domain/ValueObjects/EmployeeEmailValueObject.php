<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\EmployeeEmailException;
use App\Shared\Domain\StringNormalizer;

readonly class EmployeeEmailValueObject
{
    private string $email;

    public function __construct(string $email)
    {
        $email = StringNormalizer::normalizeEmail($email);

        if (empty($email)) {
            throw new EmployeeEmailException('El correo electrónico no puede estar vacío.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new EmployeeEmailException('Formato de correo electrónico inválido.');
        }

        $this->email = $email;
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
