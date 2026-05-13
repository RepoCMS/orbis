<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RegisterDirectStock;

/**
 * Registra stock de forma directa sobre un producto, sin pasar por el flujo
 * completo de Entrada.
 *
 * - Si $serialNumber no es null  → stock serializado (1 unidad por número de serie)
 * - Si $serialNumber es null     → stock no serializado ($quantity unidades)
 */
final readonly class RegisterDirectStockCommand
{
    public function __construct(
        public string  $productId,
        public string  $locationId,
        public ?string $serialNumber,
        public int     $quantity,
        public string  $registeredById,
    ) {
    }
}
