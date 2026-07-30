<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\AcceptByTechnician;

use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;

/**
 * El técnico acepta la solicitud de stock aprobada por el bodeguero.
 *
 * Transiciona la Request a PARTIALLY_FULFILLED y deja los items
 * en PENDING para que sean despachados individualmente via
 * FulfillRequestItemHandler.
 */
final readonly class AcceptByTechnicianHandler
{
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
    ) {
    }

    public function __invoke(AcceptByTechnicianCommand $command): void
    {
        $request = $this->requestRepository->findByIdWithItems(new RequestIdValueObject($command->requestId))
            ?? throw RequestNotFoundException::withId($command->requestId);

        $request->acceptByTechnician();
        $request->setAllItemsPending();
        $this->requestRepository->save($request);
    }
}
