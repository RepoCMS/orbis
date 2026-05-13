<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\ClientRucValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class ClientRucType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'client_ruc';
    }

    protected function getValueObjectClass(): string
    {
        return ClientRucValueObject::class;
    }
}
