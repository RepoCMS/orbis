<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\TokenHashValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class TokenHashType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'token_hash';
    }

    protected function getValueObjectClass(): string
    {
        return TokenHashValueObject::class;
    }
}
