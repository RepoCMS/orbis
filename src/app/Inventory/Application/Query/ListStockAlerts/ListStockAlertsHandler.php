<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListStockAlerts;

use App\Inventory\Application\Query\Response\StockAlertResponse;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\StockMinimumRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;

/**
 * Devuelve los umbrales cuyo stock actual está por debajo del alertThreshold.
 * Usado en el dashboard del módulo de inventario.
 *
 * @return StockAlertResponse[]
 */
final readonly class ListStockAlertsHandler
{
    public function __construct(
        private StockMinimumRepositoryInterface     $stockMinimumRepository,
        private ProductRepositoryInterface          $productRepository,
        private LocationRepositoryInterface         $locationRepository,
        private SerializedStockRepositoryInterface  $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
    ) {
    }

    /** @return StockAlertResponse[] */
    public function __invoke(ListStockAlertsQuery $query): array
    {
        $minimums = $query->locationId !== null
            ? $this->stockMinimumRepository->findByLocation(new LocationIdValueObject($query->locationId))
            : $this->stockMinimumRepository->findAll();

        $alerts = [];

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

            if (!$minimum->isBelowThreshold($currentStock)) {
                continue;
            }

            $alerts[] = new StockAlertResponse(
                stockMinimumId: (string) $minimum->id(),
                productId:      (string) $minimum->productId(),
                productName:    (string) $product->name(),
                productCode:    (string) $product->productCode(),
                locationId:     (string) $minimum->locationId(),
                locationName:   (string) $location->name(),
                currentStock:   $currentStock,
                idealQuantity:  $minimum->idealQuantity(),
                alertThreshold: $minimum->alertThreshold(),
                gap:            $minimum->idealQuantity() - $currentStock,
            );
        }

        // Most critical alerts (largest deficit) first
        usort($alerts, fn (StockAlertResponse $a, StockAlertResponse $b) => $b->gap <=> $a->gap);

        return $alerts;
    }
}
