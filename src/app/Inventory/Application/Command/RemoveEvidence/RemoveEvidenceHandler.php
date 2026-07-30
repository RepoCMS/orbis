<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RemoveEvidence;

use App\Inventory\Application\Exception\RepairEvidenceNotFoundException;
use App\Inventory\Domain\Repositories\RepairEvidenceRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RepairEvidenceIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Shared\Infrastructure\DbExceptionHandlerTrait;

final readonly class RemoveEvidenceHandler
{
    use DbExceptionHandlerTrait;

    public function __construct(
        private RepairEvidenceRepositoryInterface $evidenceRepository,
    ) {
    }

    public function __invoke(RemoveEvidenceCommand $command): void
    {
        $evidenceId = new RepairEvidenceIdValueObject($command->evidenceId);
        $repairId   = new RepairIdValueObject($command->repairId);

        $evidence = $this->evidenceRepository->findById($evidenceId)
            ?? throw RepairEvidenceNotFoundException::withId($command->evidenceId);

        $this->wrapDbExceptions(function () use ($evidence): void {
            $this->evidenceRepository->delete($evidence);
        });
    }
}
