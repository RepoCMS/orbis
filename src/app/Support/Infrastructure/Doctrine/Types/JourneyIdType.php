<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\JourneyIdValueObject;

final class JourneyIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'journey_id';
    }
    protected function getValueObjectClass(): string
    {
        return JourneyIdValueObject::class;
    }
}
