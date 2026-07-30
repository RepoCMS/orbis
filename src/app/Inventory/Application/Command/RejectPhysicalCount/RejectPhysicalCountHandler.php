<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RejectPhysicalCount;

use App\Inventory\Domain\Events\PhysicalCountRejected;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Exceptions\ApplicationException;

final readonly class RejectPhysicalCountHandler
{
    public function __construct(
        private PhysicalCountRepositoryInterface $physicalCountRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(RejectPhysicalCountCommand $command): void
    {
        $countId = new PhysicalCountIdValueObject($command->countId);
        $count = $this->physicalCountRepository->findById($countId);

        if ($count === null) {
            throw new ApplicationException('El conteo físico no existe.');
        }

        $count->reject($command->reason);

        $this->physicalCountRepository->save($count);

        $this->eventPublisher->publish(
            new PhysicalCountRejected(
                countId: $command->countId,
                reason:  $command->reason,
            ),
        );
    }
}
