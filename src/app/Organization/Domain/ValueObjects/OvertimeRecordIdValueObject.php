<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\OvertimeRecordIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class OvertimeRecordIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new OvertimeRecordIdException('Formato de ULID inválido para OvertimeRecordId.');
    }
}
