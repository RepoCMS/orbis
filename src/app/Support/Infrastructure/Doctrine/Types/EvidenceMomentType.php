<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;
use App\Support\Domain\AtmModel\EvidenceMoment;

final class EvidenceMomentType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'support_evidence_moment';
    }

    protected function getEnumClass(): string
    {
        return EvidenceMoment::class;
    }
}
