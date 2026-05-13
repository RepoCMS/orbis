<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CancelRequest;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;

final readonly class CancelRequestHandler
{
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
    ) {
    }

    public function __invoke(CancelRequestCommand $command): void
    {
        $request = $this->requestRepository->findById(new RequestIdValueObject($command->requestId));

        if ($request === null) {
            throw RequestNotFoundException::withId($command->requestId);
        }

        $request->cancel(new UserIdValueObject($command->cancelledBy));
        $this->requestRepository->save($request);
    }
}
