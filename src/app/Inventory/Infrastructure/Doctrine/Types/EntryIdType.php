<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class EntryIdType extends AbstractUlidType
{
    public function getName(): string { return 'entry_id'; }
    protected function getValueObjectClass(): string { return EntryIdValueObject::class; }
}
