<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\EntryItemIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class EntryItemIdType extends AbstractUlidType
{
    public function getName(): string { return 'entry_item_id'; }
    protected function getValueObjectClass(): string { return EntryItemIdValueObject::class; }
}
