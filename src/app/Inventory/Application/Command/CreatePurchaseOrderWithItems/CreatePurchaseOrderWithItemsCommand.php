<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreatePurchaseOrderWithItems;

/**
 * Command para crear una orden de compra con items opcionales.
 *
 * Cuando se proporcionan items, se validan y crean junto con la orden
 * en un solo handler, evitando lógica de negocio en el controller.
 *
 * @param array<int, array{productId: string, quantityOrdered: int}> $items
 */
final readonly class CreatePurchaseOrderWithItemsCommand
{
    public function __construct(
        public string $reference,
        public ?string $notes,
        public string $createdBy,
        public array $items = [],
    ) {
    }
}
