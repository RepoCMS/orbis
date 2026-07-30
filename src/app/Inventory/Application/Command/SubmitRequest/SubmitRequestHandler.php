<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\SubmitRequest;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Entities\RequestItem;
use App\Inventory\Domain\Events\RequestSubmitted;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestItemIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class SubmitRequestHandler
{
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
        private DomainEventPublisherInterface $eventPublisher,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(SubmitRequestCommand $command): void
    {
        $reference = 'SOL-' . date('Y') . '-' . strtoupper(substr($this->idGenerator->generate(), -6));

        $items = [];

        foreach ($command->items as $itemInput) {
            $items[] = RequestItem::create(
                id: new RequestItemIdValueObject($this->idGenerator->generate()),
                productId: new ProductIdValueObject($itemInput->productId),
                serializedStockId: $itemInput->serializedStockId
                    ? new SerializedStockIdValueObject($itemInput->serializedStockId)
                    : null,
                quantity: $itemInput->quantity,
            );
        }

        $request = Request::submit(
            id:                   new RequestIdValueObject($this->idGenerator->generate()),
            reference:            $reference,
            requestedBy:          new UserIdValueObject($command->requestedBy),
            sourceLocationId:     new LocationIdValueObject($command->sourceLocationId),
            destinationLocationId: new LocationIdValueObject($command->destinationLocationId),
            notes:                $command->notes,
            items:                $items,
        );

        $this->requestRepository->save($request);

        $this->eventPublisher->publish(new RequestSubmitted(
            requestId:              (string) $request->id(),
            reference:              $reference,
            requestedByUserId:      $command->requestedBy,
            productId:              !empty($items) ? (string) $items[0]->productId() : '',
            quantity:               !empty($items) ? $items[0]->quantity() : 0,
            sourceLocationId:       $command->sourceLocationId,
            sourceResponsibleUserId: null,
            notes:                  $command->notes,
        ));
    }
}
