<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class UsernameType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'username';
    }

    protected function getValueObjectClass(): string
    {
        return UsernameValueObject::class;
    }
}
