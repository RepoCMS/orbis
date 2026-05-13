<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\FindProductById;

use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Application\Query\Response\ProductResponse;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;

final readonly class FindProductByIdHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function __invoke(string $id): ProductResponse
    {
        $product = $this->productRepository->findById(new ProductIdValueObject($id))
            ?? throw ProductNotFoundException::withId($id);

        return ProductResponse::fromEntity($product);
    }
}
