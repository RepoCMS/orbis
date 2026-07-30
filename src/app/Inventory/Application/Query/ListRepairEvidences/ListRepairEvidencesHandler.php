<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\ListRepairEvidences;

use App\Inventory\Application\Query\Response\RepairEvidenceResponse;
use App\Inventory\Domain\Repositories\RepairEvidenceRepositoryInterface;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;

final readonly class ListRepairEvidencesHandler
{
    public function __construct(
        private RepairEvidenceRepositoryInterface $evidenceRepository,
    ) {
    }

    /** @return RepairEvidenceResponse[] */
    public function __invoke(ListRepairEvidencesQuery $query): array
    {
        $repairId  = new RepairIdValueObject($query->repairId);
        $evidences = $this->evidenceRepository->findByRepairId($repairId);

        return array_map(
            static fn ($evidence) => RepairEvidenceResponse::fromEntity($evidence),
            $evidences,
        );
    }
}
