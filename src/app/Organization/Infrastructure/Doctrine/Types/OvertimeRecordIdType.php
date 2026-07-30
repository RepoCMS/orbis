<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\ValueObjects\OvertimeRecordIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class OvertimeRecordIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'overtime_record_id';
    }

    protected function getValueObjectClass(): string
    {
        return OvertimeRecordIdValueObject::class;
    }
}
