<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RejectByTechnician;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;

/**
 * El técnico rechaza la solicitud de stock aprobada por el bodeguero.
 *
 * La Request pasa a REJECTED definitivamente. El técnico debe crear
 * una nueva solicitud si aún necesita el repuesto.
 */
final readonly class RejectByTechnicianHandler
{
    public function __construct(
        private RequestRepositoryInterface $requestRepository,
    ) {
    }

    public function __invoke(RejectByTechnicianCommand $command): void
    {
        $request = $this->requestRepository->findById(new RequestIdValueObject($command->requestId))
            ?? throw RequestNotFoundException::withId($command->requestId);

        $request->rejectByTechnician(
            new UserIdValueObject($command->rejectedBy),
            $command->reason,
        );

        $this->requestRepository->save($request);
    }
}
