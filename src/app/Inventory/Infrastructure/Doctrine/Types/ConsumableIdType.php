<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\ConsumableIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class ConsumableIdType extends AbstractUlidType
{
    public function getName(): string { return 'consumable_id'; }
    protected function getValueObjectClass(): string { return ConsumableIdValueObject::class; }
}
