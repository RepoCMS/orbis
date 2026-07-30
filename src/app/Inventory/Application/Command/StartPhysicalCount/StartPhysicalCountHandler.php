<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\StartPhysicalCount;

use App\Inventory\Domain\Entities\PhysicalCount;
use App\Inventory\Domain\Entities\PhysicalCountItem;
use App\Inventory\Domain\Events\PhysicalCountStarted;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use App\Inventory\Domain\ValueObjects\PhysicalCountItemIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use App\Shared\Infrastructure\DbExceptionHandlerTrait;

final readonly class StartPhysicalCountHandler
{
    use DbExceptionHandlerTrait;

    public function __construct(
        private PhysicalCountRepositoryInterface $physicalCountRepository,
        private IdGeneratorInterface $idGenerator,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(StartPhysicalCountCommand $command): string
    {
        $countId = new PhysicalCountIdValueObject($this->idGenerator->generate());

        $this->wrapDbExceptions(function () use ($command, $countId): void {
            $physicalCount = PhysicalCount::start(
                id:         $countId,
                locationId: $command->locationId,
                countedBy:  $command->countedBy,
                notes:      $command->notes,
            );

            foreach ($command->items as $itemData) {
                $itemId = new PhysicalCountItemIdValueObject($this->idGenerator->generate());

                $item = PhysicalCountItem::record(
                    id:            $itemId,
                    physicalCount: $physicalCount,
                    productCode:   $itemData['productCode'],
                    expectedQty:   $itemData['expectedQty'],
                    countedQty:    $itemData['countedQty'],
                    notes:         $itemData['notes'] ?? null,
                );

                $physicalCount->addItem($item);
            }

            $this->physicalCountRepository->save($physicalCount);

            $this->eventPublisher->publish(
                new PhysicalCountStarted(
                    countId:    (string) $countId,
                    locationId: $command->locationId,
                    countedBy:  $command->countedBy,
                ),
            );
        });

        return (string) $countId;
    }
}
