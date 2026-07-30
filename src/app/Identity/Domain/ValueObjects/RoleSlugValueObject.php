<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\RoleNameException;

/**
 * Slug único del rol para identificación en URLs y referencias externas.
 */
final readonly class RoleSlugValueObject
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 60;
    private const SLUG_PATTERN = '/^[a-z][a-z0-9_-]*[a-z0-9]$/';

    private string $slug;

    public function __construct(string $slug)
    {
        $normalized = trim($slug);

        if ($normalized === '') {
            throw new RoleNameException('El slug del rol no puede estar vacío.');
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw new RoleNameException(
                sprintf('El slug del rol debe tener al menos %d caracteres.', self::MIN_LENGTH)
            );
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new RoleNameException(
                sprintf('El slug del rol no puede exceder los %d caracteres.', self::MAX_LENGTH)
            );
        }

        if (!preg_match(self::SLUG_PATTERN, $normalized)) {
            throw new RoleNameException(
                'El slug debe comenzar con una letra minúscula y contener solo letras minúsculas, números, guiones o guiones bajos.'
            );
        }

        $this->slug = $normalized;
    }

    public static function fromString(string $slug): self
    {
        return new self($slug);
    }

    public function __toString(): string
    {
        return $this->slug;
    }
}
