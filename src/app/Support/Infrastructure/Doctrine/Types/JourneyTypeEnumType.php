<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;
use App\Support\Domain\Enums\JourneyType;

final class JourneyTypeEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'support_journey_type';
    }

    protected function getEnumClass(): string
    {
        return JourneyType::class;
    }
}
