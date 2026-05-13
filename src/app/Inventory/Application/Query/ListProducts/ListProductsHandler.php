<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListProducts;

use App\Inventory\Application\Query\Response\ProductResponse;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;

final readonly class ListProductsHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    /** @return ProductResponse[] */
    public function __invoke(): array
    {
        return array_map(
            fn ($p) => ProductResponse::fromEntity($p),
            $this->productRepository->findAll(),
        );
    }
}
