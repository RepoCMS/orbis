<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AddPurchaseOrderItem;

use App\Inventory\Application\Exception\ProductNotFoundException;
use App\Inventory\Application\Exception\PurchaseOrderNotFoundException;
use App\Inventory\Domain\Entities\PurchaseOrderItem;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderItemRepositoryInterface;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class AddPurchaseOrderItemHandler
{
    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private PurchaseOrderItemRepositoryInterface $purchaseOrderItemRepository,
        private ProductRepositoryInterface $productRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(AddPurchaseOrderItemCommand $command): void
    {
        $poId = new PurchaseOrderIdValueObject($command->purchaseOrderId);
        $this->purchaseOrderRepository->findById($poId)
            ?? throw PurchaseOrderNotFoundException::withId($command->purchaseOrderId);

        $productId = new ProductIdValueObject($command->productId);
        $this->productRepository->findById($productId)
            ?? throw ProductNotFoundException::withId($command->productId);

        $item = PurchaseOrderItem::create(
            id:               new PurchaseOrderItemIdValueObject($this->idGenerator->generate()),
            purchaseOrderId:  $poId,
            productId:        $productId,
            quantityOrdered:  $command->quantityOrdered,
        );

        $this->purchaseOrderItemRepository->save($item);
    }
}
