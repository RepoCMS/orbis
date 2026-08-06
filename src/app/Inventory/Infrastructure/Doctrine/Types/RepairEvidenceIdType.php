<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\RepairEvidenceIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class RepairEvidenceIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'repair_evidence_id';
    }
    protected function getValueObjectClass(): string
    {
        return RepairEvidenceIdValueObject::class;
    }
}
