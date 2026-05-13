<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\SerialHistoryIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class SerialHistoryIdType extends AbstractUlidType
{
    public function getName(): string { return 'serial_history_id'; }
    protected function getValueObjectClass(): string { return SerialHistoryIdValueObject::class; }
}
