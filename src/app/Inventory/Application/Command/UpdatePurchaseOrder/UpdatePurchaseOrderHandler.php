<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\UpdatePurchaseOrder;

use App\Inventory\Application\Exception\PurchaseOrderNotFoundException;
use App\Inventory\Application\Exception\PurchaseOrderReferenceAlreadyExistsException;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Shared\Infrastructure\DbExceptionHandlerTrait;

final readonly class UpdatePurchaseOrderHandler
{
    use DbExceptionHandlerTrait;

    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
    ) {
    }

    public function __invoke(UpdatePurchaseOrderCommand $command): void
    {
        $poId = new PurchaseOrderIdValueObject($command->purchaseOrderId);
        $po = $this->purchaseOrderRepository->findById($poId)
            ?? throw PurchaseOrderNotFoundException::withId($command->purchaseOrderId);

        $existing = $this->purchaseOrderRepository->findByReference($command->reference);
        if ($existing !== null && (string) $existing->id() !== (string) $poId) {
            throw PurchaseOrderReferenceAlreadyExistsException::withReference($command->reference);
        }

        $this->wrapDbExceptions(function () use ($po, $command): void {
            $po->update($command->reference, $command->notes);
            $this->purchaseOrderRepository->save($po);
        });
    }
}
