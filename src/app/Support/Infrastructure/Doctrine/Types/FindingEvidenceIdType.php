<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\FindingEvidenceIdValueObject;

final class FindingEvidenceIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'finding_evidence_id';
    }

    protected function getValueObjectClass(): string
    {
        return FindingEvidenceIdValueObject::class;
    }
}
