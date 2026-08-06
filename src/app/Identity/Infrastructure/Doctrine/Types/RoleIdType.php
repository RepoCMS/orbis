<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class RoleIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'role_id';
    }

    protected function getValueObjectClass(): string
    {
        return RoleIdValueObject::class;
    }
}
