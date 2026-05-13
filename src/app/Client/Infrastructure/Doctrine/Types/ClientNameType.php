<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\ClientNameValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class ClientNameType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'client_name';
    }

    protected function getValueObjectClass(): string
    {
        return ClientNameValueObject::class;
    }
}
