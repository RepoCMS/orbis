<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class MovementIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'movement_id';
    }

    protected function getValueObjectClass(): string
    {
        return MovementIdValueObject::class;
    }
}
