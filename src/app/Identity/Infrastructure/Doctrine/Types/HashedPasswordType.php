<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\HashedPasswordValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class HashedPasswordType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'hashed_password';
    }

    protected function getValueObjectClass(): string
    {
        return HashedPasswordValueObject::class;
    }
}
