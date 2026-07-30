<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\ValueObjects\VacationRequestIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class VacationRequestIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'vacation_request_id';
    }

    protected function getValueObjectClass(): string
    {
        return VacationRequestIdValueObject::class;
    }
}
