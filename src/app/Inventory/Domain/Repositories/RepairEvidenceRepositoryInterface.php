<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\RepairEvidence;
use App\Inventory\Domain\ValueObjects\RepairEvidenceIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;

interface RepairEvidenceRepositoryInterface
{
    public function findById(RepairEvidenceIdValueObject $id): ?RepairEvidence;

    /** @return RepairEvidence[] */
    public function findByRepairId(RepairIdValueObject $repairId): array;

    public function save(RepairEvidence $evidence): void;

    public function delete(RepairEvidence $evidence): void;
}
