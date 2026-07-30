<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\FindingComponentIdValueObject;

final class FindingComponentIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'ticket_finding_component_id';
    }

    protected function getValueObjectClass(): string
    {
        return FindingComponentIdValueObject::class;
    }
}
