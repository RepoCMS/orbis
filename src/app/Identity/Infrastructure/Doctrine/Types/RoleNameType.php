<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class RoleNameType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'role_name';
    }

    protected function getValueObjectClass(): string
    {
        return RoleNameValueObject::class;
    }
}
