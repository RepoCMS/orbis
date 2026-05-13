<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\StockByProduct;

use App\Inventory\Application\Query\Response\NonserializedStockResponse;
use App\Inventory\Application\Query\Response\SerializedStockResponse;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;

final readonly class StockByProductHandler
{
    public function __construct(
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
        private LocationRepositoryInterface $locationRepository,
    ) {
    }

    /** @return array{serialized: SerializedStockResponse[], nonserialized: NonserializedStockResponse[]} */
    public function __invoke(string $productId): array
    {
        $id          = new ProductIdValueObject($productId);
        $locationMap = $this->buildLocationMap();

        return [
            'serialized'    => array_map(
                fn ($s) => SerializedStockResponse::fromEntity(
                    $s,
                    $locationMap[(string) $s->locationId()] ?? (string) $s->locationId(),
                ),
                $this->serializedStockRepository->findByProduct($id),
            ),
            'nonserialized' => array_map(
                fn ($s) => NonserializedStockResponse::fromEntity(
                    $s,
                    $locationMap[(string) $s->locationId()] ?? (string) $s->locationId(),
                ),
                $this->nonserializedStockRepository->findByProduct($id),
            ),
        ];
    }

    /** @return array<string, string> id => name */
    private function buildLocationMap(): array
    {
        $map = [];
        foreach ($this->locationRepository->findAll() as $location) {
            $map[(string) $location->id()] = (string) $location->name();
        }

        return $map;
    }
}
