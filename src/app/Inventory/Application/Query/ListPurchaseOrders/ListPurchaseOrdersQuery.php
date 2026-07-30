<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListPurchaseOrders;

/**
 * Query para listar órdenes de compra con filtros opcionales.
 */
final readonly class ListPurchaseOrdersQuery
{
    public function __construct(
        public ?string $status = null,
        public ?string $reference = null,
    ) {
    }
}
