<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\TerminalIdAssignedByClientException;

final readonly class TerminalIdAssignedByClientValueObject
{
    private const MIN_LENGTH = 5;
    private const MAX_LENGTH = 25;

    private string $id;

    public function __construct(string $id)
    {
        $normalized = (string) preg_replace('/\s+/u', ' ', trim($id));

        if ($normalized === '') {
            throw new TerminalIdAssignedByClientException('El ID asignado por el cliente no puede estar vacío.');
        }

        $normalized = mb_strtoupper($normalized);

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw new TerminalIdAssignedByClientException(
                sprintf('El ID asignado por el cliente debe tener al menos %d caracteres.', self::MIN_LENGTH)
            );
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new TerminalIdAssignedByClientException(
                sprintf('El ID asignado por el cliente no puede exceder los %d caracteres.', self::MAX_LENGTH)
            );
        }

         if (!preg_match('/^[A-Z0-9]+$/', $normalized)) {
            throw new TerminalIdAssignedByClientException(
                'El ID asignado por el cliente solo puede contener letras y números (sin espacios ni símbolos).'
            );
        }

        $this->id = $normalized;
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
