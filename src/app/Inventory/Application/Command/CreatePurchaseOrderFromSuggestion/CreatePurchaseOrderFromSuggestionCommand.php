<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreatePurchaseOrderFromSuggestion;

/**
 * Command para crear una orden de compra directamente desde una sugerencia.
 *
 * Pre-carga el producto sugerido como item de la orden con la cantidad sugerida.
 */
final readonly class CreatePurchaseOrderFromSuggestionCommand
{
    public function __construct(
        public string $productId,
        public string $locationId,
        public int $suggestedQuantity,
        public string $reference,
        public ?string $notes,
        public string $createdBy,
    ) {
    }
}
