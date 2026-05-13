<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\TransformationIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class TransformationIdType extends AbstractUlidType
{
    public function getName(): string { return 'transformation_id'; }
    protected function getValueObjectClass(): string { return TransformationIdValueObject::class; }
}
