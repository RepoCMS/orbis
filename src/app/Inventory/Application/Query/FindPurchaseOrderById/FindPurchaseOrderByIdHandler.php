<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\FindPurchaseOrderById;

use App\Inventory\Application\Exception\PurchaseOrderNotFoundException;
use App\Inventory\Application\Query\Response\PurchaseOrderItemResponse;
use App\Inventory\Application\Query\Response\PurchaseOrderResponse;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;

final readonly class FindPurchaseOrderByIdHandler
{
    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private PurchaseOrderItemRepositoryInterface $purchaseOrderItemRepository,
    ) {
    }

    /** @return array{po: PurchaseOrderResponse, items: PurchaseOrderItemResponse[]} */
    public function __invoke(string $id): array
    {
        $poId = new PurchaseOrderIdValueObject($id);
        $po   = $this->purchaseOrderRepository->findById($poId)
            ?? throw PurchaseOrderNotFoundException::withId($id);

        $items = array_map(
            fn ($item) => PurchaseOrderItemResponse::fromEntity($item),
            $this->purchaseOrderItemRepository->findByPurchaseOrder($poId),
        );

        return [
            'po'    => PurchaseOrderResponse::fromEntity($po),
            'items' => $items,
        ];
    }
}
