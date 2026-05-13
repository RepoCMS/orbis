<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CreatePurchaseOrder;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\PurchaseOrderReferenceAlreadyExistsException;
use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Repositories\PurchaseOrderRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class CreatePurchaseOrderHandler
{
    public function __construct(
        private PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(CreatePurchaseOrderCommand $command): void
    {
        $existing = $this->purchaseOrderRepository->findByReference($command->reference);
        if ($existing !== null) {
            throw PurchaseOrderReferenceAlreadyExistsException::withReference($command->reference);
        }

        $purchaseOrder = PurchaseOrder::create(
            id:        new PurchaseOrderIdValueObject($this->idGenerator->generate()),
            reference: $command->reference,
            notes:     $command->notes,
            createdBy: new UserIdValueObject($command->createdBy),
        );

        $this->purchaseOrderRepository->save($purchaseOrder);
    }
}
