<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListPurchaseOrders;

use App\Inventory\Application\Query\Response\PurchaseOrderResponse;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;

final readonly class ListPurchaseOrdersHandler
{
    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
    ) {
    }

    /** @return PurchaseOrderResponse[] */
    public function __invoke(): array
    {
        return array_map(
            fn ($po) => PurchaseOrderResponse::fromEntity($po),
            $this->purchaseOrderRepository->findAll(),
        );
    }
}
