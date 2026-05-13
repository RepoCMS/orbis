<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\DeleteProduct;

use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;

final readonly class DeleteProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function __invoke(DeleteProductCommand $command): void
    {
        $id = new ProductIdValueObject($command->id);

        $product = $this->productRepository->findById($id)
            ?? throw ProductNotFoundException::withId($command->id);

        $product->delete();

        $this->productRepository->save($product);
    }
}
