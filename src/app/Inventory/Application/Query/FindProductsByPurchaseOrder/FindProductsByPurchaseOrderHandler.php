<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\FindProductsByPurchaseOrder;

use App\Inventory\Application\Query\Response\ProductResponse;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;

final readonly class FindProductsByPurchaseOrderHandler
{
    public function __construct(
        private PurchaseOrderItemRepositoryInterface $purchaseOrderItemRepository,
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    /** @return ProductResponse[] */
    public function __invoke(FindProductsByPurchaseOrderQuery $query): array
    {
        $poId = new PurchaseOrderIdValueObject($query->purchaseOrderId);
        $poItems = $this->purchaseOrderItemRepository->findByPurchaseOrder($poId);

        $products = [];
        foreach ($poItems as $poItem) {
            $product = $this->productRepository->findById($poItem->productId());
            if ($product !== null) {
                $products[] = ProductResponse::fromEntity($product);
            }
        }

        return $products;
    }
}
