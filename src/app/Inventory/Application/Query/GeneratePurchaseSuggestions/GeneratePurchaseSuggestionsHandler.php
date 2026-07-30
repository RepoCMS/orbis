<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\GeneratePurchaseSuggestions;

use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\StockMinimumRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseSuggestion;

/**
 * Genera sugerencias de compra basadas en umbrales de stock mínimo.
 *
 * Para cada StockMinimum configurado, calcula el stock actual del producto
 * en la ubicación y enriquece la sugerencia con la fecha de la última PO
 * que incluyó ese producto. Solo se incluyen sugerencias con gap > 0.
 *
 * @return PurchaseSuggestion[]
 */
final readonly class GeneratePurchaseSuggestionsHandler
{
    public function __construct(
        private StockMinimumRepositoryInterface $stockMinimumRepository,
        private ProductRepositoryInterface $productRepository,
        private LocationRepositoryInterface $locationRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
        private PurchaseOrderItemRepositoryInterface $purchaseOrderItemRepository,
    ) {
    }

    /** @return PurchaseSuggestion[] */
    public function __invoke(GeneratePurchaseSuggestionsQuery $query): array
    {
        $minimums = $query->locationId !== null
            ? $this->stockMinimumRepository->findByLocation(new LocationIdValueObject($query->locationId))
            : $this->stockMinimumRepository->findAll();

        $suggestions = [];

        foreach ($minimums as $minimum) {
            $product  = $this->productRepository->findById($minimum->productId());
            $location = $this->locationRepository->findById($minimum->locationId());

            if ($product === null || $location === null) {
                continue;
            }

            $currentStock = $product->isSerialized()
                ? $this->serializedStockRepository->countAvailableByProductAndLocation(
                    $minimum->productId(),
                    $minimum->locationId(),
                )
                : $this->nonserializedStockRepository->countAvailableByProductAndLocation(
                    $minimum->productId(),
                    $minimum->locationId(),
                );

            // Sólo generar sugerencia si hay déficit
            if ($currentStock >= $minimum->idealQuantity()) {
                continue;
            }

            $lastPurchaseDate = $this->purchaseOrderItemRepository
                ->findLastPurchaseDateByProduct((string) $minimum->productId());

            $suggestions[] = PurchaseSuggestion::fromAlert(
                minimum:           $minimum,
                product:           $product,
                location:          $location,
                currentStock:      $currentStock,
                lastPurchaseDate:  $lastPurchaseDate,
                frequentSupplier:  null, // Proveedor frecuente no implementado aún
            );
        }

        // Ordenar por gap descendente (mayor déficit primero)
        usort($suggestions, static fn (PurchaseSuggestion $a, PurchaseSuggestion $b) => $b->gap() <=> $a->gap());

        return $suggestions;
    }
}
