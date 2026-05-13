<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\SubmitRequest;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class SubmitRequestHandler
{
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(SubmitRequestCommand $command): void
    {
        $reference = 'SOL-' . date('Y') . '-' . strtoupper(substr($this->idGenerator->generate(), -6));

        $request = Request::submit(
            id:                   new RequestIdValueObject($this->idGenerator->generate()),
            reference:            $reference,
            requestedBy:          new UserIdValueObject($command->requestedBy),
            productId:            new ProductIdValueObject($command->productId),
            serializedStockId:    $command->serializedStockId
                ? new SerializedStockIdValueObject($command->serializedStockId)
                : null,
            quantity:             $command->quantity,
            sourceLocationId:     new LocationIdValueObject($command->sourceLocationId),
            destinationLocationId: new LocationIdValueObject($command->destinationLocationId),
            notes:                $command->notes,
        );

        $this->requestRepository->save($request);
    }
}
