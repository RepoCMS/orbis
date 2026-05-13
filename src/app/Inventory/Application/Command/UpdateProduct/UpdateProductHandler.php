<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\UpdateProduct;

use App\Inventory\Application\Exception\ProductHasStockException;
use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Domain\Repositories\NonserializedStockRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductNameValueObject;

final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private SerializedStockRepositoryInterface $serializedStockRepository,
        private NonserializedStockRepositoryInterface $nonserializedStockRepository,
    ) {
    }

    public function __invoke(UpdateProductCommand $command): void
    {
        $id      = new ProductIdValueObject($command->id);
        $product = $this->productRepository->findById($id)
            ?? throw ProductNotFoundException::withId($command->id);

        $product->update(
            name:        new ProductNameValueObject($command->name),
            description: $command->description,
        );

        if ($command->trackingType !== null && $command->trackingType !== $product->trackingType()) {
            $hasStock = !empty($this->serializedStockRepository->findByProduct($id))
                || !empty($this->nonserializedStockRepository->findByProduct($id));

            if ($hasStock) {
                throw ProductHasStockException::cannotChangeTrackingType();
            }

            $product->changeTrackingType($command->trackingType);
        }

        $this->productRepository->save($product);
    }
}
