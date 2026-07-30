<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;
use App\Support\Domain\AtmModel\FindingType;

final class FindingTypeType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'support_finding_type';
    }

    protected function getEnumClass(): string
    {
        return FindingType::class;
    }
}
