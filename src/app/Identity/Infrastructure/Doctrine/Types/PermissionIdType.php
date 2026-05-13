<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class PermissionIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'permission_id';
    }

    protected function getValueObjectClass(): string
    {
        return PermissionIdValueObject::class;
    }
}
