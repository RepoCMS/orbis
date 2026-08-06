<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\Enums\SerialHistoryReason;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class SerialHistoryReasonEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'inventory_serial_history_reason';
    }
    protected function getEnumClass(): string
    {
        return SerialHistoryReason::class;
    }
}
