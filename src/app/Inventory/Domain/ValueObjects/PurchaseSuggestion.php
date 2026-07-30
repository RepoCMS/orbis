<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\Entities\StockMinimum;
use DateTimeImmutable;

/**
 * Value Object que representa una sugerencia de compra para un producto en una ubicación.
 *
 * No es una entidad persistida; se genera en tiempo real a partir del estado actual
 * del inventario y los umbrales de stock mínimo. Se usa como DTO de dominio para
 * alimentar el listado de sugerencias y la creación rápida de órdenes de compra.
 */
final readonly class PurchaseSuggestion
{
    public function __construct(
        public ProductIdValueObject $productId,
        public string $productCode,
        public string $productName,
        public LocationIdValueObject $locationId,
        public string $locationName,
        public int $currentStock,
        public int $idealQuantity,
        public int $alertThreshold,
        public int $suggestedQuantity,
        public ?DateTimeImmutable $lastPurchaseDate,
        public ?float $lastPurchasePrice,
        public ?string $frequentSupplier,
        public DateTimeImmutable $generatedAt,
    ) {
    }

    /**
     * Factory que construye una sugerencia a partir de un StockMinimum,
     * su Product asociado, Location y el stock actual calculado.
     */
    public static function fromAlert(
        StockMinimum $minimum,
        Product $product,
        Location $location,
        int $currentStock,
        ?DateTimeImmutable $lastPurchaseDate = null,
        ?string $frequentSupplier = null,
    ): self {
        $suggestedQuantity = max(0, $minimum->idealQuantity() - $currentStock);

        return new self(
            productId:         $minimum->productId(),
            productCode:       (string) $product->productCode(),
            productName:       (string) $product->name(),
            locationId:        $minimum->locationId(),
            locationName:      (string) $location->name(),
            currentStock:      $currentStock,
            idealQuantity:     $minimum->idealQuantity(),
            alertThreshold:    $minimum->alertThreshold(),
            suggestedQuantity: $suggestedQuantity,
            lastPurchaseDate:  $lastPurchaseDate,
            lastPurchasePrice: null,
            frequentSupplier:  $frequentSupplier,
            generatedAt:       new DateTimeImmutable(),
        );
    }

    /**
     * El gap (déficit) entre stock actual y cantidad ideal.
     * Útil para ordenar sugerencias por criticidad.
     */
    public function gap(): int
    {
        return $this->suggestedQuantity;
    }
}
