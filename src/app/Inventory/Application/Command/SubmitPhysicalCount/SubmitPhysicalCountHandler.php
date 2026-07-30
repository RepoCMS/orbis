<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\SubmitPhysicalCount;

use App\Inventory\Domain\Events\PhysicalCountSubmitted;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Exceptions\ApplicationException;

final readonly class SubmitPhysicalCountHandler
{
    public function __construct(
        private PhysicalCountRepositoryInterface $physicalCountRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(SubmitPhysicalCountCommand $command): void
    {
        $countId = new PhysicalCountIdValueObject($command->countId);
        $count = $this->physicalCountRepository->findById($countId);

        if ($count === null) {
            throw new ApplicationException('El conteo físico no existe.');
        }

        $itemCount = count($count->items());
        $count->submit();

        $this->physicalCountRepository->save($count);

        $this->eventPublisher->publish(
            new PhysicalCountSubmitted(
                countId:    $command->countId,
                locationId: $count->locationId(),
                itemCount:  $itemCount,
            ),
        );
    }
}
