<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CancelRequest;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Domain\Events\RequestItemCancelled;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class CancelRequestHandler
{
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
        private DomainEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(CancelRequestCommand $command): void
    {
        $request = $this->requestRepository->findById(new RequestIdValueObject($command->requestId));

        if ($request === null) {
            throw RequestNotFoundException::withId($command->requestId);
        }

        $request->cancel(new UserIdValueObject($command->cancelledBy));

        $events = [];
        foreach ($request->items() as $item) {
            $events[] = new RequestItemCancelled(
                requestId:        (string) $request->id(),
                requestReference: $request->reference(),
                itemId:           (string) $item->id(),
                productId:        (string) $item->productId(),
            );
        }

        $this->requestRepository->save($request);

        if ($events !== []) {
            $this->eventPublisher->publish(...$events);
        }
    }
}
