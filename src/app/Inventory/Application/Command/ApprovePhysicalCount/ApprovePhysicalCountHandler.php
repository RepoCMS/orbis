<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ApprovePhysicalCount;

use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Enums\MovementType;
use App\Inventory\Domain\Events\PhysicalCountApproved;
use App\Inventory\Domain\Repositories\MovementRepositoryInterface;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;
use App\Inventory\Domain\Repositories\ProductRepositoryInterface;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;


final readonly class ApprovePhysicalCountHandler
{
    public function __construct(
        private PhysicalCountRepositoryInterface $physicalCountRepository,
        private ProductRepositoryInterface $productRepository,
        private MovementRepositoryInterface $movementRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(ApprovePhysicalCountCommand $command): void
    {
        $countId = new PhysicalCountIdValueObject($command->countId);
        $count = $this->physicalCountRepository->findById($countId);

        if ($count === null) {
            throw new \InvalidArgumentException('El conteo físico no existe.');
        }

        $count->approve($command->approvedBy);

        $adjustedCount = 0;

        foreach ($count->itemsNeedingAdjustment() as $item) {
            $productCode = new ProductCodeAssignedBySupplierValueObject($item->productCode());
            $product = $this->productRepository->findByCode($productCode);

            if ($product === null) {
                throw new \InvalidArgumentException(
                    sprintf('El producto con código "%s" no existe.', $item->productCode())
                );
            }

            $movementId = new MovementIdValueObject($this->idGenerator->generate());

            $movement = Movement::create(
                id:                   $movementId,
                productId:            $product->id(),
                type:                 MovementType::ADJUSTMENT,
                sourceLocationId:     null,
                destinationLocationId: null,
                serializedStockId:    null,
                quantity:             $item->difference(),
                notes:                sprintf(
                    'Ajuste por conteo físico %s: %s (esperado: %d, contado: %d)',
                    $command->countId,
                    $item->productCode(),
                    $item->expectedQty(),
                    $item->countedQty(),
                ),
            );

            $this->movementRepository->save($movement);
            $adjustedCount++;
        }

        $this->physicalCountRepository->save($count);

        $this->eventPublisher->publish(
            new PhysicalCountApproved(
                countId:       $command->countId,
                approvedBy:    $command->approvedBy,
                adjustedItems: $adjustedCount,
            ),
        );
    }
}
