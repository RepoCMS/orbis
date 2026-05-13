<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DefineStockMinimum;

use App\Inventory\Domain\Entities\StockMinimum;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\StockMinimumRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

/**
 * Crea o actualiza (upsert) el umbral mínimo de stock para un producto
 * en una ubicación concreta. Un solo comando cubre alta y edición.
 */
final readonly class DefineStockMinimumHandler
{
    public function __construct(
        private StockMinimumRepositoryInterface $stockMinimumRepository,
        private ProductRepositoryInterface      $productRepository,
        private LocationRepositoryInterface     $locationRepository,
        private IdGeneratorInterface            $idGenerator,
    ) {
    }

    public function __invoke(DefineStockMinimumCommand $command): void
    {
        $productId  = new ProductIdValueObject($command->productId);
        $locationId = new LocationIdValueObject($command->locationId);

        if ($this->productRepository->findById($productId) === null) {
            throw new \LogicException('El producto no existe.');
        }

        if ($this->locationRepository->findById($locationId) === null) {
            throw new \LogicException('La ubicación no existe.');
        }

        $existing = $this->stockMinimumRepository->findByProductAndLocation($productId, $locationId);

        if ($existing !== null) {
            $existing->update($command->idealQuantity, $command->alertThreshold);
            $this->stockMinimumRepository->save($existing);

            return;
        }

        $minimum = StockMinimum::define(
            id:             new StockMinimumIdValueObject($this->idGenerator->generate()),
            productId:      $productId,
            locationId:     $locationId,
            idealQuantity:  $command->idealQuantity,
            alertThreshold: $command->alertThreshold,
        );

        $this->stockMinimumRepository->save($minimum);
    }
}
