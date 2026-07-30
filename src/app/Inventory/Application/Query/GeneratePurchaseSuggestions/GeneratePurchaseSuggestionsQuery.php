<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\GeneratePurchaseSuggestions;

/**
 * Query para generar sugerencias de compra.
 *
 * Si se proporciona locationId, filtra solo las sugerencias para esa ubicación.
 * Si es null, genera sugerencias para todos los umbrales configurados.
 */
final readonly class GeneratePurchaseSuggestionsQuery
{
    public function __construct(
        public ?string $locationId = null,
    ) {
    }
}
