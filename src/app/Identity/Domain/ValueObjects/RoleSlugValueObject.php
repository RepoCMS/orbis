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

    /**
     * Transliteración de caracteres acentuados a su equivalente ASCII.
     */
    private const ACCENT_MAP = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c', 'ý' => 'y',
    ];

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

    /**
     * Deriva el slug a partir del nombre legible de un rol.
     *
     * "Técnico de Campo" → "tecnico_de_campo"
     * "Administrador"    → "administrador"
     */
    public static function fromName(string $name): self
    {
        $normalized = mb_strtolower(trim($name));

        $normalized = strtr($normalized, self::ACCENT_MAP);
        $normalized = (string) preg_replace('/[^a-z0-9]+/', '_', $normalized);
        $normalized = trim((string) preg_replace('/_+/', '_', $normalized), '_');

        if ($normalized === '') {
            throw new RoleNameException('El nombre del rol no se puede convertir en un slug válido.');
        }

        if (!preg_match('/^[a-z]/', $normalized)) {
            $normalized = 'r_' . $normalized;
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            $normalized = mb_substr($normalized, 0, self::MAX_LENGTH);
        }

        $normalized = (string) preg_replace('/[^a-z0-9]+$/', '', $normalized);

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw new RoleNameException('El nombre del rol no se puede convertir en un slug válido.');
        }

        return new self($normalized);
    }

    public function __toString(): string
    {
        return $this->slug;
    }
}
