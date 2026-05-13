<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;
use App\Support\Domain\Enums\RescheduleRequestedBy;

final class RescheduleRequestedByEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'support_reschedule_requested_by';
    }

    protected function getEnumClass(): string
    {
        return RescheduleRequestedBy::class;
    }
}
