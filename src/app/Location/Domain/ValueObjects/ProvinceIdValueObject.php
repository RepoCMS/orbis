<?php

declare(strict_types=1);

namespace App\Location\Domain\ValueObjects;

use App\Location\Domain\Exceptions\ProvinceIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class ProvinceIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ProvinceIdException('Formato de ULID invalido para ProvinceId.');
    }
}
