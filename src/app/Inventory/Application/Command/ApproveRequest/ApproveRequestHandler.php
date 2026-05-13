<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ApproveRequest;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Domain\Events\RequestAwaitingTechnicianAcceptance;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class ApproveRequestHandler
{
    public function __construct(
        private RequestRepositoryInterface   $requestRepository,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(ApproveRequestCommand $command): void
    {
        $request = $this->requestRepository->findById(new RequestIdValueObject($command->requestId));

        if ($request === null) {
            throw RequestNotFoundException::withId($command->requestId);
        }

        $approvedBy = new UserIdValueObject($command->approvedBy);

        // PENDING → APPROVED → AWAITING_TECHNICIAN_ACCEPTANCE (un solo paso)
        $request->approve($approvedBy);
        $request->notifyTechnicianAcceptance();

        $this->requestRepository->save($request);

        $this->publisher->publish(new RequestAwaitingTechnicianAcceptance(
            requestId:          (string) $request->id(),
            reference:          $request->reference(),
            requestedByUserId:  (string) $request->requestedBy(),
            approvedByUserId:   (string) $approvedBy,
        ));
    }
}
