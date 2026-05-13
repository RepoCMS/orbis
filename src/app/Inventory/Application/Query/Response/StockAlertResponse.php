<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

/**
 * Resultado de una alerta de stock por debajo del umbral mínimo.
 * Usada en el dashboard del coordinador/bodeguero.
 */
final readonly class StockAlertResponse
{
    public function __construct(
        public string $stockMinimumId,
        public string $productId,
        public string $productName,
        public string $productCode,
        public string $locationId,
        public string $locationName,
        public int    $currentStock,
        public int    $idealQuantity,
        public int    $alertThreshold,
        public int    $gap,           // idealQuantity - currentStock
    ) {
    }
}
