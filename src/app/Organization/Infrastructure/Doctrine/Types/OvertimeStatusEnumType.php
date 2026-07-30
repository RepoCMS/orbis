<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\Enums\OvertimeStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class OvertimeStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'overtime_status';
    }

    protected function getEnumClass(): string
    {
        return OvertimeStatus::class;
    }
}
