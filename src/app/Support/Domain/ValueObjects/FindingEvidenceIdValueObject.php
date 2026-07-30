<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\FindingEvidenceException;

final readonly class FindingEvidenceIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new FindingEvidenceException('Formato de ID de evidence inválido.');
    }
}
