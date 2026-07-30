<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class RoleSlugType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'role_slug';
    }

    protected function getValueObjectClass(): string
    {
        return RoleSlugValueObject::class;
    }
}
