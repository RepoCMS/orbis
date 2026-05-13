<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class PermissionKeyType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'permission_key';
    }

    protected function getValueObjectClass(): string
    {
        return PermissionKeyValueObject::class;
    }
}
