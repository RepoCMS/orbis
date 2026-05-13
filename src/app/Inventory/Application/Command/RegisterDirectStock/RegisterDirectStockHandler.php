<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RegisterDirectStock;

use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class RegisterDirectStockHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private LocationRepositoryInterface $locationRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
        private MovementRepositoryInterface $movementRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(RegisterDirectStockCommand $command): void
    {
        $productId  = new ProductIdValueObject($command->productId);
        $locationId = new LocationIdValueObject($command->locationId);

        $product = $this->productRepository->findById($productId);
        if ($product === null) {
            throw ProductNotFoundException::withId($command->productId);
        }

        if ($this->locationRepository->findById($locationId) === null) {
            throw LocationNotFoundException::withId($command->locationId);
        }

        // Guard: validar coherencia tipo de producto ↔ tipo de stock
        if ($command->serialNumber !== null && !$product->isSerialized()) {
            throw new \DomainException(
                sprintf('El producto "%s" es no serializado. No puede registrarse stock con número de serie.', (string) $product->name())
            );
        }
        if ($command->serialNumber === null && $product->isSerialized()) {
            throw new \DomainException(
                sprintf('El producto "%s" es serializado. Debe indicar un número de serie.', (string) $product->name())
            );
        }

        if ($command->serialNumber !== null) {
            // ── Serializado: un ítem por número de serie ──────────────────
            $stock = SerializedStock::register(
                id:           new SerializedStockIdValueObject($this->idGenerator->generate()),
                productId:    $productId,
                serialNumber: new StockSerialNumberValueObject($command->serialNumber),
                locationId:   $locationId,
            );
            $this->serializedStockRepository->save($stock);

            $movement = Movement::create(
                id:                    new MovementIdValueObject($this->idGenerator->generate()),
                productId:             $productId,
                type:                  MovementType::ENTRY,
                sourceLocationId:      null,
                destinationLocationId: $locationId,
                serializedStockId:     $stock->id(),
                quantity:              1,
                notes:                 'Carga directa de stock',
            );
            $movement->complete();
            $this->movementRepository->save($movement);
        } else {
            // ── No serializado: agregar a lote existente o crear uno nuevo ─
            $existing = $this->nonserializedStockRepository->findByProductAndLocation(
                $productId,
                $locationId,
            );

            if ($existing !== null && $existing->isAvailable()) {
                $existing->addStock($command->quantity);
                $this->nonserializedStockRepository->save($existing);
            } else {
                $newStock = NonserializedStock::initialize(
                    id:              new NonserializedStockIdValueObject($this->idGenerator->generate()),
                    productId:       $productId,
                    locationId:      $locationId,
                    initialQuantity: $command->quantity,
                );
                $this->nonserializedStockRepository->save($newStock);
            }

            $movement = Movement::create(
                id:                    new MovementIdValueObject($this->idGenerator->generate()),
                productId:             $productId,
                type:                  MovementType::ENTRY,
                sourceLocationId:      null,
                destinationLocationId: $locationId,
                serializedStockId:     null,
                quantity:              $command->quantity,
                notes:                 'Carga directa de stock',
            );
            $movement->complete();
            $this->movementRepository->save($movement);
        }
    }
}
