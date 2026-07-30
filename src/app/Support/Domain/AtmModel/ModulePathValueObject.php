<?php

declare(strict_types=1);

namespace App\Support\Domain\AtmModel;

use App\Support\Domain\Exceptions\TicketModuleFindingException;

/**
 * Value Object que representa la ruta jerárquica de un módulo en el árbol de ATM.
 *
 * Formato válido: "BRM > BMU > BC" (solo letras mayúsculas, números, espacios y "/").
 */
final readonly class ModulePathValueObject
{
    private const PATH_PATTERN = '/^[A-Z][A-Z0-9\/\s]+( > [A-Z][A-Z0-9\/\s]+)*$/';

    public function __construct(
        public string $path,
    ) {
        if (!preg_match(self::PATH_PATTERN, $path)) {
            throw new TicketModuleFindingException(
                'Formato de modulePath inválido. Use: "BRM > BMU > BC"',
            );
        }
    }
}
