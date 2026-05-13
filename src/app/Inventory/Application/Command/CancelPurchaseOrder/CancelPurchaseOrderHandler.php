<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CancelPurchaseOrder;

use App\Inventory\Application\Exception\PurchaseOrderNotFoundException;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;

final readonly class CancelPurchaseOrderHandler
{
    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
    ) {
    }

    public function __invoke(CancelPurchaseOrderCommand $command): void
    {
        $id = new PurchaseOrderIdValueObject($command->id);
        $po = $this->purchaseOrderRepository->findById($id)
            ?? throw PurchaseOrderNotFoundException::withId($command->id);

        $po->cancel();

        $this->purchaseOrderRepository->save($po);
    }
}
