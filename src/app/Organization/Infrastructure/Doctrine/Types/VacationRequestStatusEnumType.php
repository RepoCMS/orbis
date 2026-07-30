<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\Enums\VacationRequestStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class VacationRequestStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'vacation_request_status';
    }

    protected function getEnumClass(): string
    {
        return VacationRequestStatus::class;
    }
}
