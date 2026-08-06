<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\RmaRecordIdValueObject;

final class RmaRecordIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'rma_record_id';
    }
    protected function getValueObjectClass(): string
    {
        return RmaRecordIdValueObject::class;
    }
}
