<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\ContractIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class ContractIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'contract_id';
    }

    protected function getValueObjectClass(): string
    {
        return ContractIdValueObject::class;
    }
}
