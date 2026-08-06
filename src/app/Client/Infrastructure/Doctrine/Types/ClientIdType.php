<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class ClientIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'client_id';
    }

    protected function getValueObjectClass(): string
    {
        return ClientIdValueObject::class;
    }
}
