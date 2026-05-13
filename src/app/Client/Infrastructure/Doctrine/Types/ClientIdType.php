<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Client\Domain\ValueObjects\ClientIdValueObject;

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
